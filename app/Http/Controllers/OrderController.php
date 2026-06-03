<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Event;
use App\Models\Payment;
use App\Models\Promotion;
use App\Models\Ticket;
use App\Models\TicketOrder;
use App\Models\TicketType;
use App\Models\User;
use App\Services\CustomerNotificationService;
use App\Services\CrmSyncService;
use App\Services\QrCodeService;
use App\Services\SlipQrDecoderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function store(Request $request, CrmSyncService $crm, SlipQrDecoderService $slipQrDecoder, CustomerNotificationService $notifications, QrCodeService $qrCode): RedirectResponse
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:40'],
            'customer_email' => ['nullable', 'email'],
            'payment_method' => ['required', 'in:bank_transfer,qr_payment,cash'],
            'payment_account_key' => ['nullable', 'string', 'max:80'],
            'payment_note' => ['nullable', 'string', 'max:1000'],
            'terms_accepted' => ['required', 'accepted'],
            'coupon_code' => ['nullable', 'string', 'max:40'],
            'slip' => ['nullable', 'image', 'max:4096'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.ticket_type_id' => ['required', 'exists:ticket_types,id'],
            'items.*.quantity' => ['required', 'integer', 'min:0', 'max:20'],
            'items.*.holders' => ['nullable', 'array'],
            'items.*.holders.*' => ['nullable', 'string', 'max:255'],
        ]);

        $selected = collect($data['items'])->filter(fn ($item) => (int) $item['quantity'] > 0);

        if ($selected->isEmpty()) {
            return back()->withErrors(['items' => 'Please select at least one ticket. / กรุณาเลือกตั๋วอย่างน้อย 1 ใบ'])->withInput();
        }

        $slipPath = $request->file('slip')?->store('payment-slips', 'uploads');

        $order = DB::transaction(function () use ($data, $selected, $slipPath, $request, $crm, $slipQrDecoder, $qrCode) {
            $user = $this->syncCustomerProfile($request->user(), $data, $crm);
            $ticketTypes = TicketType::query()
                ->with('event')
                ->whereIn('id', $selected->pluck('ticket_type_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $eventIds = $ticketTypes->pluck('event_id')->unique();
            $events = $ticketTypes->pluck('event')->unique('id');

            $selectedPaymentAccount = $events->first()?->paymentOption($data['payment_account_key'] ?? null, $data['payment_method']);
            $paymentAllowed = $events->every(fn (Event $event) => (bool) $event->paymentOption($data['payment_account_key'] ?? null, $data['payment_method']));
            if (! $paymentAllowed) {
                throw ValidationException::withMessages([
                    'payment_method' => 'This payment method is not available for the selected ticket. / วิธีชำระเงินนี้ยังไม่เปิดใช้สำหรับตั๋วที่เลือก',
                ]);
            }

            $subtotal = 0;
            foreach ($selected as $item) {
                $ticketType = $ticketTypes[(int) $item['ticket_type_id']];
                abort_unless($ticketType->isOnSale(), 422, 'Ticket type is not available. / ประเภทตั๋วนี้ยังไม่เปิดขาย');
                abort_if($ticketType->availableQuantity() < (int) $item['quantity'], 422, 'Not enough tickets available. / จำนวนตั๋วไม่เพียงพอ');
                $subtotal += $ticketType->price_thb * (int) $item['quantity'];
            }

            $coupon = null;
            $couponDiscount = 0;
            if (! empty($data['coupon_code'])) {
                $coupon = Coupon::query()
                    ->where('code', strtoupper($data['coupon_code']))
                    ->where(fn ($query) => $query->whereNull('event_id')->orWhereIn('event_id', $eventIds))
                    ->first();
                if ($coupon) {
                    $couponDiscount = $coupon->discountForItems($selected, $ticketTypes);
                    if ($couponDiscount > 0) {
                        $coupon->increment('used_count');
                    }
                }
            }

            $promotions = Promotion::query()
                ->where(fn ($query) => $query->whereNull('event_id')->orWhereIn('event_id', $eventIds))
                ->get();
            $promotionDiscount = 0;
            $remainingDiscountable = max(0, $subtotal - $couponDiscount);
            foreach ($promotions as $promotion) {
                $discount = $promotion->discountForItems($selected, $ticketTypes, $couponDiscount > 0);
                $appliedDiscount = min($remainingDiscountable, $discount);

                if ($appliedDiscount > 0) {
                    $promotionDiscount += $appliedDiscount;
                    $remainingDiscountable -= $appliedDiscount;
                    $promotion->increment('used_count');
                }
            }

            $discount = $couponDiscount + $promotionDiscount;
            $total = max(0, $subtotal - $discount);
            $autoApprove = $total === 0;

            if ($total > 0 && $data['payment_method'] !== 'cash' && ! $slipPath) {
                throw ValidationException::withMessages([
                    'slip' => 'Please attach a payment slip for QR payment or bank transfer. / กรุณาแนบสลิปสำหรับการชำระด้วย QR หรือโอนธนาคาร',
                ]);
            }

            $order = TicketOrder::create([
                'order_number' => $this->generateOrderNumber($ticketTypes),
                'user_id' => $user?->id,
                'coupon_id' => $coupon?->id,
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'] ?? null,
                'status' => $autoApprove ? 'approved' : 'pending',
                'subtotal_thb' => $subtotal,
                'discount_thb' => $discount,
                'total_thb' => $total,
                'payment_method' => $data['payment_method'],
                'payment_note' => $data['payment_note'] ?? null,
                'payment_slip_path' => $slipPath,
                'approved_at' => $autoApprove ? now() : null,
                'approved_by' => $autoApprove ? $user?->id : null,
            ]);

            foreach ($selected as $item) {
                $ticketType = $ticketTypes[(int) $item['ticket_type_id']];
                $quantity = (int) $item['quantity'];
                $orderItem = $order->items()->create([
                    'event_id' => $ticketType->event_id,
                    'ticket_type_id' => $ticketType->id,
                    'quantity' => $quantity,
                    'unit_price_thb' => $ticketType->price_thb,
                    'line_total_thb' => $ticketType->price_thb * $quantity,
                ]);

                for ($i = 0; $i < $quantity; $i++) {
                    $holderName = trim((string) ($item['holders'][$i] ?? ''));

                    $ticket = $order->tickets()->create([
                        'uuid' => (string) Str::uuid(),
                        'order_item_id' => $orderItem->id,
                        'event_id' => $ticketType->event_id,
                        'ticket_type_id' => $ticketType->id,
                        'user_id' => $user?->id,
                        'holder_name' => $holderName ?: $data['customer_name'],
                        'holder_phone' => $data['customer_phone'],
                        'status' => $autoApprove ? 'approved' : 'pending',
                    ]);

                    if ($autoApprove) {
                        $ticket->ticketType()->increment('sold_count');
                    }
                }
            }

            $expectedPromptPayId = $data['payment_method'] === 'qr_payment' && ! empty($selectedPaymentAccount['account_number'])
                ? $qrCode->promptPayIdentifier((string) $selectedPaymentAccount['account_number'])
                : null;

            $payment = Payment::create([
                'ticket_order_id' => $order->id,
                'method' => $data['payment_method'],
                'payment_account_key' => $selectedPaymentAccount['key'] ?? ($data['payment_account_key'] ?? null),
                'payment_account_label' => $selectedPaymentAccount['label'] ?? null,
                'payment_account_name' => $selectedPaymentAccount['account_name'] ?? null,
                'payment_account_number' => $selectedPaymentAccount['account_number'] ?? null,
                'amount_thb' => $order->total_thb,
                'expected_amount_thb' => $order->total_thb,
                'expected_promptpay_id' => $expectedPromptPayId,
                'status' => $autoApprove ? 'waived' : ($data['payment_method'] === 'cash' ? 'cash_pending' : 'submitted'),
                'slip_path' => $slipPath,
                'note' => $data['payment_note'] ?? null,
            ]);

            if (! $autoApprove && $data['payment_method'] !== 'cash') {
                $payment->update($slipQrDecoder->review($slipPath, $payment->fresh()->toArray(), $payment));
            }

            return $order;
        });

        $crm->pushOrderActivity($order, 'ticket_order_created');
        $notifications->orderCreatedForAdmins($order);

        $parameters = ['order' => $order];

        if (! $request->user()) {
            $parameters['phone'] = $order->customer_phone;
        }

        return redirect()->route('orders.show', $parameters)->with('status', 'Order created. Admin approval will activate tickets. / สร้างออเดอร์แล้ว รอแอดมินอนุมัติเพื่อเปิดใช้งานตั๋ว');
    }

    private function syncCustomerProfile(?User $user, array $data, CrmSyncService $crm): ?User
    {
        if (! $user || $user->isAdmin()) {
            return $user;
        }

        if ($customer = $crm->pullCustomer([
            'phone' => $data['customer_phone'],
            'email' => $data['customer_email'] ?? null,
            'line_user_id' => $user->provider === 'line' ? $user->provider_id : null,
        ])) {
            $user = $crm->applyCustomerToUser($user, $customer);
        }

        $updates = [
            'name' => $data['customer_name'],
            'phone' => $data['customer_phone'],
        ];

        if (! empty($data['customer_email'])) {
            $emailIsAvailable = ! User::query()
                ->where('email', $data['customer_email'])
                ->whereKeyNot($user->id)
                ->exists();

            if ($emailIsAvailable) {
                $updates['email'] = $data['customer_email'];
            }
        }

        $user->update($updates);
        $crm->pushCustomer($user->fresh(), 'checkout');

        return $user->refresh();
    }

    public function show(TicketOrder $order): View
    {
        abort_unless(auth()->id() === $order->user_id || request('phone') === $order->customer_phone || auth()->user()?->isAdmin(), 403);

        $order->load(['user', 'items.event', 'items.ticketType', 'tickets.event', 'tickets.ticketType']);

        return view('orders.show', compact('order'));
    }

    public function ticket(string $uuid): View
    {
        $ticket = Ticket::with(['event', 'ticketType', 'order'])->where('uuid', $uuid)->firstOrFail();

        abort_unless(auth()->id() === $ticket->user_id || request('phone') === $ticket->holder_phone || auth()->user()?->isAdmin(), 403);

        return view('tickets.show', compact('ticket'));
    }

    public function updateTicketHolder(Request $request, TicketOrder $order, Ticket $ticket): RedirectResponse
    {
        abort_unless($ticket->ticket_order_id === $order->id, 404);
        abort_unless(auth()->id() === $order->user_id || request('phone') === $order->customer_phone || auth()->user()?->isAdmin(), 403);
        abort_unless($order->status === 'approved', 403, 'Ticket holder names can be edited after payment approval. / แก้ไขชื่อผู้ถือบัตรได้หลังอนุมัติการชำระเงิน');

        $data = $request->validate([
            'holder_name' => ['required', 'string', 'max:255'],
        ]);

        $ticket->update([
            'holder_name' => trim($data['holder_name']),
        ]);

        return back()
            ->with('status', 'Ticket holder name updated. / อัปเดตชื่อผู้ถือบัตรแล้ว');
    }

    public function ticketQr(string $uuid, QrCodeService $qrCode): Response
    {
        $ticket = Ticket::where('uuid', $uuid)->firstOrFail();

        return response($qrCode->svg($qrCode->ticketPayload($ticket)), 200)
            ->header('Content-Type', 'image/svg+xml');
    }

    public function paymentQr(Event $event, Request $request, QrCodeService $qrCode): Response
    {
        $amount = max(0, (float) $request->input('amount', 0));
        $account = $event->paymentOption($request->string('account')->toString(), 'qr_payment');

        return response($qrCode->svg($qrCode->paymentPayload($event, $amount, $account)), 200)
            ->header('Content-Type', 'image/svg+xml');
    }

    private function generateOrderNumber($ticketTypes): string
    {
        $eventName = $ticketTypes->first()?->event?->name ?? 'Event';
        $words = collect(preg_split('/[^A-Za-z0-9]+/', $eventName, -1, PREG_SPLIT_NO_EMPTY));
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
}
