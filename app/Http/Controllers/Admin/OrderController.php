<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\TicketType;
use App\Models\TicketOrder;
use App\Services\CrmSyncService;
use App\Services\CustomerNotificationService;
use App\Services\SlipQrDecoderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = TicketOrder::with(['items.event', 'items.ticketType', 'tickets'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('event_id'), fn ($query) => $query->whereHas('items', fn ($items) => $items->where('event_id', $request->integer('event_id'))))
            ->when($request->filled('ticket_type_id'), fn ($query) => $query->whereHas('items', fn ($items) => $items->where('ticket_type_id', $request->integer('ticket_type_id'))));

        $events = $this->eventsForUser($request);

        if ($request->user()->role !== 'super_admin') {
            $orders->whereHas('items.event.assignedUsers', fn ($query) => $query->whereKey($request->user()->id));
        }

        return view('admin.orders.index', [
            'orders' => $orders->latest()->paginate(20)->withQueryString(),
            'events' => $events,
            'ticketTypes' => $this->ticketTypesForEvents($events, $request),
        ]);
    }

    public function show(Request $request, TicketOrder $order): View
    {
        $order->load([
            'user',
            'items.event',
            'items.ticketType',
            'tickets.event',
            'tickets.ticketType',
            'payments' => fn ($query) => $query->latest(),
        ]);
        $this->authorizeOrder($request, $order);
        $navigationQuery = TicketOrder::query();

        if ($request->user()->role !== 'super_admin') {
            $navigationQuery->whereHas('items.event.assignedUsers', fn ($query) => $query->whereKey($request->user()->id));
        }

        $previousOrder = (clone $navigationQuery)
            ->where('id', '<', $order->id)
            ->latest('id')
            ->first();
        $nextOrder = (clone $navigationQuery)
            ->where('id', '>', $order->id)
            ->oldest('id')
            ->first();

        return view('admin.orders.show', compact('order', 'previousOrder', 'nextOrder'));
    }

    public function approve(Request $request, TicketOrder $order, CustomerNotificationService $notifications, CrmSyncService $crm): RedirectResponse
    {
        $order->loadMissing('items.event');
        $this->authorizeOrder($request, $order);

        if (! $this->canTransition($order, 'approved')) {
            return back()->withErrors(['status' => 'Only pending orders can be approved. / อนุมัติได้เฉพาะออเดอร์ที่รอตรวจสอบ']);
        }

        $order->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $request->user()->id,
        ]);

        foreach ($order->tickets as $ticket) {
            $ticket->update(['status' => 'approved']);
            $ticket->ticketType()->increment('sold_count');
        }

        $notifications->orderApproved($order);
        $crm->pushOrderActivity($order->fresh(), 'ticket_order_approved');

        return back()->with('status', 'Order approved and tickets activated.');
    }

    public function reject(Request $request, TicketOrder $order): RedirectResponse
    {
        $order->loadMissing('items.event');
        $this->authorizeOrder($request, $order);

        if (! $this->canTransition($order, 'rejected')) {
            return back()->withErrors(['status' => 'Only pending orders can be rejected. / ปฏิเสธได้เฉพาะออเดอร์ที่รอตรวจสอบ']);
        }

        $order->update(['status' => 'rejected']);
        $order->tickets()->update(['status' => 'rejected']);

        return back()->with('status', 'Order rejected.');
    }

    public function cancel(Request $request, TicketOrder $order): RedirectResponse
    {
        $order->loadMissing('items.event');
        $this->authorizeOrder($request, $order);

        if (! $this->canTransition($order, 'cancelled')) {
            return back()->withErrors(['status' => 'Only approved orders can be cancelled. / ยกเลิกได้เฉพาะออเดอร์ที่อนุมัติแล้ว']);
        }

        $order->update(['status' => 'cancelled']);
        $order->tickets()->update(['status' => 'cancelled']);

        return back()->with('status', 'Order cancelled.');
    }

    public function refund(Request $request, TicketOrder $order): RedirectResponse
    {
        $order->loadMissing('items.event');
        $this->authorizeOrder($request, $order);

        if (! $this->canTransition($order, 'refunded')) {
            return back()->withErrors(['status' => 'Only approved orders can be refunded. / คืนเงินได้เฉพาะออเดอร์ที่อนุมัติแล้ว']);
        }

        $order->update(['status' => 'refunded']);
        $order->tickets()->update(['status' => 'refunded']);

        return back()->with('status', 'Order marked refunded.');
    }

    public function checkSlipQr(Request $request, TicketOrder $order, SlipQrDecoderService $slipQrDecoder): RedirectResponse
    {
        $order->loadMissing(['items.event', 'payments']);
        $this->authorizeOrder($request, $order);

        if (! $order->payment_slip_path) {
            return back()->with('status', 'No payment slip found for this order. / ไม่พบสลิปของออเดอร์นี้');
        }

        $payment = $order->payments()->latest()->first()
            ?? $order->payments()->create([
                'method' => $order->payment_method,
                'amount_thb' => $order->total_thb,
                'status' => 'submitted',
                'slip_path' => $order->payment_slip_path,
            ]);

        $decoded = array_merge([
            'slip_path' => $order->payment_slip_path,
        ], $slipQrDecoder->decode($order->payment_slip_path));

        $payment->update($decoded);
        $payment->update($slipQrDecoder->withDuplicateReview($decoded, $payment));

        return back()->with('status', 'Payment slip QR checked. / ตรวจ QR จากสลิปแล้ว');
    }

    public function destroy(Request $request, TicketOrder $order): RedirectResponse
    {
        $order->loadMissing('items.event');
        $this->authorizeOrder($request, $order);

        if (! in_array($order->status, ['cancelled', 'refunded'], true)) {
            return back()->withErrors(['status' => 'Cancel or refund the order before deleting it. / กรุณายกเลิกหรือคืนเงินก่อนลบออเดอร์']);
        }

        $order->delete();

        return redirect()->route('admin.orders.index')->with('status', 'Order deleted.');
    }

    private function canTransition(TicketOrder $order, string $nextStatus): bool
    {
        return match ($nextStatus) {
            'approved', 'rejected' => $order->status === 'pending',
            'cancelled', 'refunded' => $order->status === 'approved',
            default => false,
        };
    }

    private function authorizeOrder(Request $request, TicketOrder $order): void
    {
        if ($request->user()->role === 'super_admin') {
            return;
        }

        abort_unless($order->items->every(fn ($item) => $request->user()->canManageEvent($item->event)), 403);
    }

    private function eventsForUser(Request $request)
    {
        $events = Event::query()->with('ticketTypes')->orderBy('starts_at');

        if ($request->user()->role !== 'super_admin') {
            $events->whereHas('assignedUsers', fn ($query) => $query->whereKey($request->user()->id));
        }

        return $events->get();
    }

    private function ticketTypesForEvents($events, Request $request)
    {
        $ticketTypes = TicketType::query()
            ->with('event')
            ->whereIn('event_id', $events->pluck('id'))
            ->when($request->filled('event_id'), fn ($query) => $query->where('event_id', $request->integer('event_id')))
            ->orderBy('event_id')
            ->orderBy('name')
            ->get();

        return $ticketTypes;
    }
}
