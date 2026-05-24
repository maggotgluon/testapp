<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Payment;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketOrder;
use App\Models\TicketType;
use App\Models\User;
use App\Services\CrmSyncService;
use App\Services\QrCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function store(Request $request, CrmSyncService $crm): RedirectResponse
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:40'],
            'customer_email' => ['nullable', 'email'],
            'payment_method' => ['required', 'in:bank_transfer,qr_payment'],
            'payment_note' => ['nullable', 'string', 'max:1000'],
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

        $order = DB::transaction(function () use ($data, $selected, $slipPath, $request, $crm) {
            $user = $this->syncCustomerProfile($request->user(), $data, $crm);
            $ticketTypes = TicketType::query()
                ->with('event')
                ->whereIn('id', $selected->pluck('ticket_type_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $subtotal = 0;
            foreach ($selected as $item) {
                $ticketType = $ticketTypes[(int) $item['ticket_type_id']];
                abort_unless($ticketType->isOnSale(), 422, 'Ticket type is not available. / ประเภทตั๋วนี้ยังไม่เปิดขาย');
                abort_if($ticketType->availableQuantity() < (int) $item['quantity'], 422, 'Not enough tickets available. / จำนวนตั๋วไม่เพียงพอ');
                $subtotal += $ticketType->price_thb * (int) $item['quantity'];
            }

            $coupon = null;
            $discount = 0;
            if (! empty($data['coupon_code'])) {
                $eventIds = $ticketTypes->pluck('event_id')->unique();
                $coupon = Coupon::query()
                    ->where('code', strtoupper($data['coupon_code']))
                    ->where(fn ($query) => $query->whereNull('event_id')->orWhereIn('event_id', $eventIds))
                    ->first();
                if ($coupon) {
                    $discount = $coupon->discountForItems($selected, $ticketTypes);
                    if ($discount > 0) {
                        $coupon->increment('used_count');
                    }
                }
            }

            $order = TicketOrder::create([
                'order_number' => $this->generateOrderNumber($ticketTypes),
                'user_id' => $user?->id,
                'coupon_id' => $coupon?->id,
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'] ?? null,
                'status' => 'pending',
                'subtotal_thb' => $subtotal,
                'discount_thb' => $discount,
                'total_thb' => max(0, $subtotal - $discount),
                'payment_method' => $data['payment_method'],
                'payment_note' => $data['payment_note'] ?? null,
                'payment_slip_path' => $slipPath,
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

                    $order->tickets()->create([
                        'uuid' => (string) Str::uuid(),
                        'order_item_id' => $orderItem->id,
                        'event_id' => $ticketType->event_id,
                        'ticket_type_id' => $ticketType->id,
                        'user_id' => $user?->id,
                        'holder_name' => $holderName ?: $data['customer_name'],
                        'holder_phone' => $data['customer_phone'],
                    ]);
                }
            }

            Payment::create([
                'ticket_order_id' => $order->id,
                'method' => $data['payment_method'],
                'amount_thb' => $order->total_thb,
                'status' => 'submitted',
                'slip_path' => $slipPath,
                'note' => $data['payment_note'] ?? null,
            ]);

            return $order;
        });

        $crm->pushOrderActivity($order, 'ticket_order_created');

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

    public function ticketQr(string $uuid, QrCodeService $qrCode): Response
    {
        $ticket = Ticket::where('uuid', $uuid)->firstOrFail();

        return response($qrCode->svg($qrCode->ticketPayload($ticket)), 200)
            ->header('Content-Type', 'image/svg+xml');
    }

    public function paymentQr(Event $event, Request $request, QrCodeService $qrCode): Response
    {
        $amount = max(0, (int) $request->integer('amount'));

        return response($qrCode->svg($qrCode->paymentPayload($event, $amount)), 200)
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
