<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Promotion;
use App\Models\Ticket;
use App\Models\TicketOrder;
use App\Services\EventDescriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function show(Event $event): View
    {
        abort_if(! $event->is_published || $event->ends_at->isPast(), 404);

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

        return view('events.show', compact('event', 'eventDescriptionHtml'));
    }

    public function profile(Request $request): View
    {
        $orders = TicketOrder::query()
            ->with(['items.event', 'items.ticketType', 'tickets.event', 'tickets.ticketType'])
            ->where('user_id', $request->user()->id)
            ->whereHas('items.event', fn ($query) => $query->where('ends_at', '>=', now()))
            ->latest()
            ->get();

        $tickets = Ticket::query()
            ->with(['event', 'ticketType', 'order'])
            ->where('user_id', $request->user()->id)
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

        return view('profile', compact('orders', 'tickets', 'orderEvents', 'ticketEvents', 'activeView'));
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
