<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Survey;
use App\Models\Ticket;
use App\Models\TicketOrder;
use App\Models\TicketType;
use App\Services\SurveyGate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SurveyController extends Controller
{
    public function show(Request $request, Survey $survey, SurveyGate $gate): View
    {
        abort_unless($survey->is_active, 404);

        if ($request->filled('return')) {
            $gate->rememberReturn($survey, $request, (string) $request->query('return'));
        }

        if ($gate->hasCompleted($survey, $request)) {
            return view('surveys.completed', [
                'survey' => $survey,
                'returnTo' => $gate->returnTo($survey, $request),
            ]);
        }

        $response = $gate->responseFor($survey, $request);

        // Pre-fill survey answers from checkout session or authenticated user
        if (empty($response->answers)) {
            $prefill = $this->buildPrefillAnswers($survey, $request);
            if (! empty($prefill)) {
                $response->answers = $prefill;
            }
        }

        return view('surveys.show', [
            'survey' => $survey,
            'response' => $response,
        ]);
    }

    public function store(Request $request, Survey $survey, SurveyGate $gate): RedirectResponse
    {
        abort_unless($survey->is_active, 404);

        $response = $gate->responseFor($survey, $request);
        $answers = $this->mergeOtherFields($survey, $request->input('answers', []));
        $answers = $this->cleanAnswers($survey, $answers);
        $isDraft = $request->input('action') === 'draft';

        if (! $isDraft) {
            $this->validateRequiredAnswers($survey, $answers);
            $this->validateSelectAnswers($survey, $answers);
        }

        $response->update([
            'user_id' => $request->user()?->id,
            'session_id' => $request->session()->getId(),
            'answers' => $answers,
            'meta' => $this->captureMeta($request),
            'status' => $isDraft ? 'draft' : 'completed',
            'completed_at' => $isDraft ? null : now(),
        ]);

        if ($isDraft) {
            return back()->with('status', 'Survey progress saved. / บันทึกคำตอบชั่วคราวแล้ว');
        }

        $request->session()->put('survey_completed.'.$survey->id, true);

        if ($survey->placement === 'free_ticket_gate') {
            return $this->claimFreeTicket($survey, $request, $gate);
        }

        return redirect($gate->returnTo($survey, $request))->with('status', 'Survey completed. / ทำแบบสอบถามเสร็จแล้ว');
    }

    private function claimFreeTicket(Survey $survey, Request $request, SurveyGate $gate): RedirectResponse
    {
        $eventId = $request->session()->pull('survey_free_ticket_event_id', $survey->event_id);

        if (! $eventId || ! ($event = Event::find($eventId))) {
            return redirect($gate->returnTo($survey, $request))->with('status', 'Survey completed. / ทำแบบสอบถามเสร็จแล้ว');
        }

        $freeTicketType = $event->ticketTypes()
            ->where('price_thb', 0)
            ->where('status', 'active')
            ->orderBy('id')
            ->first();

        if (! $freeTicketType) {
            return redirect($gate->returnTo($survey, $request))->with('status', 'Survey completed. / ทำแบบสอบถามเสร็จแล้ว');
        }

        // Redirect logged-in users to existing ticket instead of creating a duplicate
        if ($request->user()) {
            $existingTicket = Ticket::query()
                ->where('event_id', $event->id)
                ->where('ticket_type_id', $freeTicketType->id)
                ->where('user_id', $request->user()->id)
                ->where('status', 'approved')
                ->latest()
                ->first();

            if ($existingTicket) {
                $ticketUrl = route('tickets.show', $existingTicket->uuid);
                $request->session()->flash('survey_redirect_url', $ticketUrl);
                $request->session()->flash('survey_redirect_message', 'You already have a ticket! / คุณมีตั๋วอยู่แล้ว!');
                $request->session()->put('survey_free_ticket_uuid_'.($survey->event_id ?: $event->id), $existingTicket->uuid);
                return redirect()->route('surveys.granted', $survey);
            }
        }

        $result = DB::transaction(function () use ($freeTicketType, $event, $survey, $request) {
            $ticketType = TicketType::query()
                ->whereKey($freeTicketType->id)
                ->lockForUpdate()
                ->first();

            abort_unless($ticketType->isOnSale(), 422, 'Ticket type is not available.');

            // Extract customer name from survey answers or auth user
            $customerName = $this->extractNameFromAnswers($survey, $request);

            $order = TicketOrder::create([
                'order_number' => $this->generateOrderNumber($event),
                'user_id' => $request->user()?->id,
                'customer_name' => $customerName,
                'customer_phone' => '',
                'customer_email' => null,
                'status' => 'approved',
                'subtotal_thb' => 0,
                'discount_thb' => 0,
                'total_thb' => 0,
                'payment_method' => 'free',
                'approved_at' => now(),
                'approved_by' => $request->user()?->id,
            ]);

            $orderItem = $order->items()->create([
                'event_id' => $event->id,
                'ticket_type_id' => $ticketType->id,
                'quantity' => 1,
                'unit_price_thb' => 0,
                'line_total_thb' => 0,
            ]);

            $ticket = $order->tickets()->create([
                'uuid' => (string) Str::uuid(),
                'order_item_id' => $orderItem->id,
                'event_id' => $event->id,
                'ticket_type_id' => $ticketType->id,
                'user_id' => $request->user()?->id,
                'holder_name' => $customerName,
                'holder_phone' => '',
                'status' => 'approved',
            ]);

            $ticketType->increment('sold_count');

            Payment::create([
                'ticket_order_id' => $order->id,
                'method' => 'free',
                'amount_thb' => 0,
                'status' => 'waived',
            ]);

            return ['order' => $order, 'ticket' => $ticket];
        });

        $ticketUrl = route('tickets.show', $result['ticket']->uuid);

        $request->session()->flash('survey_redirect_url', $ticketUrl);
        $request->session()->flash('survey_redirect_message', 'Free ticket granted! Redirecting to your ticket... / รับตั๋วฟรีแล้ว! กำลังพาไปยังหน้าตั๋ว...');
        $request->session()->put('survey_free_ticket_uuid_'.($survey->event_id ?: $event->id), $result['ticket']->uuid);

        return redirect()->route('surveys.granted', $survey);
    }

    public function granted(Request $request, Survey $survey): View
    {
        abort_unless($survey->is_active, 404);

        $redirectUrl = $request->session()->pull('survey_redirect_url', route('events.index'));
        $message = $request->session()->pull('survey_redirect_message', 'Redirecting...');
        $delay = (int) env('SURVEY_REDIRECT_DELAY', 5);

        return view('surveys.granted', compact('redirectUrl', 'message', 'delay'));
    }

    private function extractNameFromAnswers(Survey $survey, Request $request): string
    {
        // Try auth user name first
        if ($request->user()?->name) {
            return $request->user()->name;
        }

        // Try from survey answers
        $response = $survey->responses()
            ->where('session_id', $request->session()->getId())
            ->latest()
            ->first();

        if ($response && ! empty($response->answers)) {
            foreach ($survey->questions ?? [] as $question) {
                $key = $question['key'] ?? '';
                $label = strtolower((string) ($question['label'] ?? ''));
                $keyLower = strtolower($key);

                if (str_contains($keyLower, 'name') || str_contains($label, 'name') || str_contains($label, 'ชื่อ')) {
                    $value = trim((string) ($response->answers[$key] ?? ''));
                    if ($value !== '') {
                        return $value;
                    }
                }
            }
        }

        return 'Guest';
    }

    private function captureMeta(Request $request): array
    {
        return array_filter([
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referer' => $request->header('referer'),
            'locale' => $request->getLocale(),
            'screen' => $request->input('meta.screen'),
            'timezone' => $request->input('meta.timezone'),
            'language' => $request->input('meta.language'),
            'platform' => $request->input('meta.platform'),
            'cookies_enabled' => $request->input('meta.cookies_enabled'),
        ], fn ($v) => $v !== null && $v !== '');
    }

    private function generateOrderNumber(Event $event): string
    {
        $words = collect(preg_split('/[^A-Za-z0-9]+/', $event->name, -1, PREG_SPLIT_NO_EMPTY));
        $prefix = $words->map(fn ($word) => Str::upper(Str::substr($word, 0, 1)))->take(4)->join('') ?: 'EVT';
        $prefix = Str::padRight($prefix, 3, 'X');
        $date = now()->format('md');
        $base = "{$prefix}-{$date}";
        $sequence = TicketOrder::where('order_number', 'like', $base.'-%')->count() + 1;

        do {
            $orderNumber = $base.'-'.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
            $sequence++;
        } while (TicketOrder::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    /**
     * Build pre-fill answers for a survey from checkout session data or authenticated user.
     * Matches question keys (or labels) that represent name, email, or phone fields.
     */
    private function buildPrefillAnswers(Survey $survey, Request $request): array
    {
        $userInfo = $request->session()->get('checkout_user_info', []);

        if (empty($userInfo) && $user = $request->user()) {
            $userInfo = [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
            ];
        }

        if (empty(array_filter($userInfo))) {
            return [];
        }

        $prefill = [];

        foreach ($survey->questions ?? [] as $question) {
            $key = $question['key'] ?? Str::slug($question['label'] ?? Str::random(8), '_');
            $label = strtolower((string) ($question['label'] ?? ''));
            $type = $question['type'] ?? 'text';

            // Skip multi-value types — we only pre-fill text/email fields
            if (in_array($type, ['checkboxes', 'radio', 'select'], true)) {
                continue;
            }

            $keyLower = strtolower($key);

            if (! empty($userInfo['name']) && (str_contains($keyLower, 'name') || str_contains($label, 'name') || str_contains($label, 'ชื่อ'))) {
                $prefill[$key] = $userInfo['name'];
            } elseif (! empty($userInfo['email']) && ($type === 'email' || str_contains($keyLower, 'email') || str_contains($label, 'email') || str_contains($label, 'อีเมล'))) {
                $prefill[$key] = $userInfo['email'];
            } elseif (! empty($userInfo['phone']) && (str_contains($keyLower, 'phone') || str_contains($keyLower, 'tel') || str_contains($keyLower, 'mobile') || str_contains($label, 'phone') || str_contains($label, 'โทร') || str_contains($label, 'เบอร์'))) {
                $prefill[$key] = $userInfo['phone'];
            }
        }

        return $prefill;
    }

    private function mergeOtherFields(Survey $survey, array $answers): array
    {
        foreach ($survey->questions ?? [] as $question) {
            $key = $question['key'] ?? '';
            if (empty($key) || empty($question['hasOther'])) {
                continue;
            }

            $otherKey = $key.'_other';
            $otherValue = trim((string) ($answers[$otherKey] ?? ''));
            unset($answers[$otherKey]);

            if (! isset($answers[$key])) {
                continue;
            }

            $type = $question['type'] ?? 'text';

            if ($type === 'checkboxes' && is_array($answers[$key])) {
                $answers[$key] = array_map(function ($value) use ($otherValue) {
                    return $value === 'Other:' && $otherValue !== '' ? 'Other: '.$otherValue : $value;
                }, $answers[$key]);
            } elseif ($answers[$key] === 'Other:' && $otherValue !== '') {
                $answers[$key] = 'Other: '.$otherValue;
            }
        }

        return $answers;
    }

    private function cleanAnswers(Survey $survey, array $input): array
    {
        return collect($survey->questions ?? [])
            ->mapWithKeys(function (array $question) use ($input) {
                $key = $question['key'] ?? Str::slug($question['label'] ?? Str::random(8), '_');
                $type = $question['type'] ?? 'text';
                $value = $input[$key] ?? null;

                if ($type === 'checkboxes') {
                    $value = collect(is_array($value) ? $value : [])
                        ->map(fn ($item) => trim((string) $item))
                        ->filter()
                        ->values()
                        ->all();
                } else {
                    $value = is_array($value) ? null : trim((string) $value);
                }

                return [$key => $value];
            })
            ->all();
    }

    private function validateSelectAnswers(Survey $survey, array $answers): void
    {
        $invalid = collect($survey->questions ?? [])
            ->filter(function (array $question) use ($answers) {
                $type = $question['type'] ?? 'text';
                if (! in_array($type, ['select', 'radio', 'checkboxes'], true)) {
                    return false;
                }

                $key = $question['key'] ?? '';
                $options = $question['options'] ?? [];
                $hasOther = ! empty($question['hasOther']);
                $answer = $answers[$key] ?? null;

                if ($answer === null || $answer === '' || (is_array($answer) && empty($answer))) {
                    return false;
                }

                if ($type === 'checkboxes') {
                    foreach ((array) $answer as $value) {
                        $valid = in_array($value, $options, true)
                            || ($hasOther && str_starts_with((string) $value, 'Other:'));
                        if (! $valid) {
                            return true;
                        }
                    }
                    return false;
                }

                $valid = in_array((string) $answer, $options, true)
                    || ($hasOther && str_starts_with((string) $answer, 'Other:'));
                return ! $valid;
            })
            ->pluck('label')
            ->filter()
            ->values();

        if ($invalid->isNotEmpty()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'answers' => 'Invalid selection for: '.$invalid->join(', '),
            ]);
        }
    }

    private function validateRequiredAnswers(Survey $survey, array $answers): void
    {
        $missing = collect($survey->questions ?? [])
            ->filter(function (array $question) use ($answers) {
                if (empty($question['required'])) {
                    return false;
                }

                $key = $question['key'] ?? '';
                $answer = $answers[$key] ?? null;

                if (is_array($answer)) {
                    $hasOther = ! empty($question['hasOther']) && in_array('Other:', $answer, true);
                    return $answer === [] || ($hasOther && count($answer) === 1);
                }

                $trimmed = trim((string) $answer);
                if ($trimmed === '') {
                    return true;
                }

                if (! empty($question['hasOther']) && $trimmed === 'Other:') {
                    return true;
                }

                return false;
            })
            ->pluck('label')
            ->filter()
            ->values();

        if ($missing->isNotEmpty()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'answers' => 'Please answer: '.$missing->join(', '),
            ]);
        }
    }
}
