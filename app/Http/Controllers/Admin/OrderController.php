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
        return view('admin.orders.index', [
            'orders' => TicketOrder::with(['items.event', 'items.ticketType', 'tickets'])
                ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
                ->latest()
                ->paginate(20),
        ]);
    }

    public function show(TicketOrder $order): View
    {
        $order->load(['items.event', 'items.ticketType', 'tickets.event', 'tickets.ticketType']);

        return view('admin.orders.show', compact('order'));
    }

    public function approve(Request $request, TicketOrder $order): RedirectResponse
    {
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

    public function reject(TicketOrder $order): RedirectResponse
    {
        $order->update(['status' => 'rejected']);
        $order->tickets()->update(['status' => 'rejected']);

        return back()->with('status', 'Order rejected.');
    }

    public function refund(TicketOrder $order): RedirectResponse
    {
        $order->update(['status' => 'refunded']);
        $order->tickets()->update(['status' => 'refunded']);

        return back()->with('status', 'Order marked refunded.');
    }
}
