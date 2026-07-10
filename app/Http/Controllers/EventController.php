<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Promotion;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\Ticket;
use App\Models\TicketOrder;
use App\Models\User;
use App\Services\EventDescriptionService;
use App\Services\SurveyGate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EventController extends Controller
{
    public function __construct(private EventDescriptionService $descriptions)
    {
    }

    public function index(): View|RedirectResponse
    {
        $events = Event::query()
            ->visible()
            ->with(['ticketTypes' => fn ($query) => $query
                ->where('status', 'active')
                ->where(fn ($query) => $query->whereNull('sale_starts_at')->orWhere('sale_starts_at', '<=', now()))
                ->where(fn ($query) => $query->whereNull('sale_ends_at')->orWhere('sale_ends_at', '>=', now()))])
            ->orderBy('starts_at')
            ->get();

        if ($events->count() === 1) {
            return redirect()->route('events.show', $events->first());
        }

        return view('events.index', compact('events'));
    }

    public function show(Request $request, Event $event, SurveyGate $surveys): View|RedirectResponse
    {
        abort_if(! $event->is_published || $event->ends_at->isPast(), 404);

        foreach (['before_event_view', 'before_ticket_selection'] as $placement) {
            if ($survey = $surveys->due($placement, $request, $event)) {
                $surveys->rememberReturn($survey, $request, $request->getRequestUri());

                return redirect()->route('surveys.show', $survey);
            }
        }

        $event->load([
            'ticketTypes' => fn ($query) => $query
                ->where('status', 'active')
                ->where(fn ($query) => $query->whereNull('sale_starts_at')->orWhere('sale_starts_at', '<=', now()))
                ->where(fn ($query) => $query->whereNull('sale_ends_at')->orWhere('sale_ends_at', '>=', now())),
            'coupons' => fn ($query) => $query
                ->where('is_active', true)
                ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>=', now()))
                ->where(fn ($query) => $query->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit')),
            'promotions' => fn ($query) => $query
                ->where('is_active', true)
                ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>=', now()))
                ->where(fn ($query) => $query->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit')),
        ]);

        $availableTicketTypeIds = $event->ticketTypes->pluck('id');
        $globalPromotions = Promotion::query()
            ->whereNull('event_id')
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>=', now()))
            ->where(fn ($query) => $query->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit'))
            ->get();
        $event->setRelation('coupons', $event->coupons->filter(
            fn ($coupon) => $coupon->ticket_type_id === null || $availableTicketTypeIds->contains($coupon->ticket_type_id)
        )->values());
        $event->setRelation('promotions', $event->promotions->merge($globalPromotions)->filter(
            fn ($promotion) => $promotion->ticket_type_id === null || $availableTicketTypeIds->contains($promotion->ticket_type_id)
        )->values());
        $eventDescriptionHtml = $this->descriptions->render($event->description, $event->description_format ?? 'html');
        $freeApprovalSurvey = $surveys->due('before_free_order_approval', $request, $event);
        $freeApprovalSurveyUrl = $freeApprovalSurvey
            ? route('surveys.show', ['survey' => $freeApprovalSurvey, 'return' => $request->getRequestUri().'#checkout'])
            : null;

        // Check if the event qualifies for "free ticket gate" flow
        $freeTicketGateSurveyUrl = null;
        $hasFreeTicketOnly = $event->ticketTypes->isNotEmpty() && $event->ticketTypes->every(fn ($t) => $t->price_thb === 0);

        // If user already has a free ticket for this event, send them straight to it
        if ($hasFreeTicketOnly) {
            $existingUuid = $request->session()->get('survey_free_ticket_uuid_'.$event->id);
            if ($existingUuid) {
                if (Ticket::where('uuid', $existingUuid)->exists()) {
                    return redirect()->route('tickets.show', $existingUuid);
                }
                $request->session()->forget('survey_free_ticket_uuid_'.$event->id);
            }

            if ($request->user()) {
                $existingTicket = Ticket::query()
                    ->where('event_id', $event->id)
                    ->where('user_id', $request->user()->id)
                    ->where('status', 'approved')
                    ->latest()
                    ->first();

                if ($existingTicket) {
                    return redirect()->route('tickets.show', $existingTicket->uuid);
                }
            }
        }

        if ($hasFreeTicketOnly && ($survey = $surveys->due('free_ticket_gate', $request, $event))) {
            $request->session()->put('survey_free_ticket_event_id', $event->id);
            $surveys->rememberReturn($survey, $request, $request->getRequestUri());
            $freeTicketGateSurveyUrl = route('surveys.show', $survey);
        }

        // Extract name/email/phone from completed survey answers for this event to pre-fill checkout
        $surveyPrefill = $this->extractSurveyPrefill($event, $request);

        return view('events.show', compact('event', 'eventDescriptionHtml', 'freeApprovalSurveyUrl', 'freeTicketGateSurveyUrl', 'hasFreeTicketOnly', 'surveyPrefill'));
    }

    /**
     * Extract name/email/phone from the user's completed survey answers for this event,
     * so the checkout form can be pre-filled with info the user already provided.
     */
    private function extractSurveyPrefill(Event $event, Request $request): array
    {
        $surveyIds = Survey::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('event_id')->orWhere('event_id', $event->id))
            ->pluck('id');

        if ($surveyIds->isEmpty()) {
            return [];
        }

        $responseQuery = SurveyResponse::query()
            ->whereIn('survey_id', $surveyIds)
            ->where('status', 'completed')
            ->with('survey')
            ->where(function ($q) use ($request) {
                if ($request->user()) {
                    $q->where('user_id', $request->user()->id);
                }
                $q->orWhere('session_id', $request->session()->getId());
            })
            ->latest('completed_at');

        $prefill = ['name' => null, 'email' => null, 'phone' => null];

        foreach ($responseQuery->get() as $response) {
            $answers = $response->answers ?? [];
            $questions = $response->survey?->questions ?? [];

            foreach ($questions as $question) {
                $key = $question['key'] ?? Str::slug($question['label'] ?? '', '_');
                $label = strtolower((string) ($question['label'] ?? ''));
                $keyLower = strtolower($key);
                $value = trim((string) ($answers[$key] ?? ''));

                if (! $value) {
                    continue;
                }

                if (! $prefill['name'] && (str_contains($keyLower, 'name') || str_contains($label, 'name') || str_contains($label, 'ชื่อ'))) {
                    $prefill['name'] = $value;
                } elseif (! $prefill['email'] && (str_contains($keyLower, 'email') || str_contains($label, 'email') || str_contains($label, 'อีเมล'))) {
                    $prefill['email'] = $value;
                } elseif (! $prefill['phone'] && (str_contains($keyLower, 'phone') || str_contains($keyLower, 'tel') || str_contains($keyLower, 'mobile') || str_contains($label, 'phone') || str_contains($label, 'โทร') || str_contains($label, 'เบอร์'))) {
                    $prefill['phone'] = $value;
                }
            }

            if ($prefill['name'] && $prefill['email'] && $prefill['phone']) {
                break;
            }
        }

        return array_filter($prefill);
    }

    public function profile(Request $request, ?User $user = null): View
    {
        $profileUser = $user ?: $request->user();
        abort_unless($profileUser, 403);

        if ($user) {
            abort_unless($request->user()?->role === 'super_admin', 403);
        }

        $orders = TicketOrder::query()
            ->with(['items.event', 'items.ticketType', 'tickets.event', 'tickets.ticketType'])
            ->where('user_id', $profileUser->id)
            ->whereHas('items.event', fn ($query) => $query->where('ends_at', '>=', now()))
            ->latest()
            ->get();

        $tickets = Ticket::query()
            ->with(['event', 'ticketType', 'order'])
            ->where('user_id', $profileUser->id)
            ->whereHas('event', fn ($query) => $query->where('ends_at', '>=', now()))
            ->latest()
            ->get();

        $orderEvents = $orders
            ->flatMap(fn ($order) => $order->items->pluck('event'))
            ->filter()
            ->filter(fn ($event) => $event->ends_at->isFuture())
            ->unique('id')
            ->values();

        $ticketEvents = $tickets
            ->pluck('event')
            ->filter()
            ->filter(fn ($event) => $event->ends_at->isFuture())
            ->unique('id')
            ->values();

        $activeView = $request->query('view') === 'tickets' ? 'tickets' : 'orders';
        $isViewingAsUser = (bool) $user;

        return view('profile', compact('orders', 'tickets', 'orderEvents', 'ticketEvents', 'activeView', 'profileUser', 'isViewingAsUser'));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users')->ignore($request->user()->id)],
            'phone' => ['nullable', 'string', 'max:40'],
        ]);

        $request->user()->update($data);

        return back()->with('status', 'Profile updated. / อัปเดตโปรไฟล์แล้ว');
    }

    public function lookup(Request $request): View
    {
        $orders = collect();

        $isAdmin = in_array($request->user()?->role, ['super_admin', 'event_admin'], true);

        if ($isAdmin && ($request->filled('phone') || $request->filled('order_number'))) {
            $orders = TicketOrder::query()
                ->with(['items.event', 'items.ticketType', 'tickets.event', 'tickets.ticketType'])
                ->when($request->filled('phone'), fn ($query) => $query->where('customer_phone', 'like', '%'.$request->string('phone').'%'))
                ->when($request->filled('order_number'), fn ($query) => $query->where('order_number', 'like', '%'.strtoupper((string) $request->string('order_number')).'%'))
                ->when($request->user()->role !== 'super_admin', fn ($query) => $query->whereHas('items.event.assignedUsers', fn ($assigned) => $assigned->whereKey($request->user()->id)))
                ->latest()
                ->get();
        } elseif ($request->filled(['phone', 'order_number'])) {
            $orders = TicketOrder::query()
                ->with(['items.event', 'items.ticketType', 'tickets.event', 'tickets.ticketType'])
                ->where('customer_phone', $request->string('phone'))
                ->where('order_number', strtoupper((string) $request->string('order_number')))
                ->latest()
                ->get();
        }

        return view('orders.lookup', compact('orders', 'isAdmin'));
    }
}
