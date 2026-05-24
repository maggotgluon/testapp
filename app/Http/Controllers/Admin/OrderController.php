<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TicketOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = TicketOrder::with(['items.event', 'items.ticketType', 'tickets'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')));

        if ($request->user()->role !== 'super_admin') {
            $orders->whereHas('items.event.assignedUsers', fn ($query) => $query->whereKey($request->user()->id));
        }

        return view('admin.orders.index', [
            'orders' => $orders->latest()->paginate(20),
        ]);
    }

    public function show(Request $request, TicketOrder $order): View
    {
        $order->load(['user', 'items.event', 'items.ticketType', 'tickets.event', 'tickets.ticketType']);
        $this->authorizeOrder($request, $order);

        return view('admin.orders.show', compact('order'));
    }

    public function approve(Request $request, TicketOrder $order): RedirectResponse
    {
        $order->loadMissing('items.event');
        $this->authorizeOrder($request, $order);

        if ($order->status === 'approved') {
            return back()->with('status', 'Order is already approved.');
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

        return back()->with('status', 'Order approved and tickets activated.');
    }

    public function reject(Request $request, TicketOrder $order): RedirectResponse
    {
        $order->loadMissing('items.event');
        $this->authorizeOrder($request, $order);

        $order->update(['status' => 'rejected']);
        $order->tickets()->update(['status' => 'rejected']);

        return back()->with('status', 'Order rejected.');
    }

    public function refund(Request $request, TicketOrder $order): RedirectResponse
    {
        $order->loadMissing('items.event');
        $this->authorizeOrder($request, $order);

        $order->update(['status' => 'refunded']);
        $order->tickets()->update(['status' => 'refunded']);

        return back()->with('status', 'Order marked refunded.');
    }

    public function destroy(Request $request, TicketOrder $order): RedirectResponse
    {
        $order->loadMissing('items.event');
        $this->authorizeOrder($request, $order);
        $order->delete();

        return redirect()->route('admin.orders.index')->with('status', 'Order deleted.');
    }

    private function authorizeOrder(Request $request, TicketOrder $order): void
    {
        if ($request->user()->role === 'super_admin') {
            return;
        }

        abort_unless($order->items->every(fn ($item) => $request->user()->canManageEvent($item->event)), 403);
    }
}
