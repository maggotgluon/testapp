<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\TicketOrder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(): View
    {
        $events = Event::query()
            ->visible()
            ->with(['ticketTypes' => fn ($query) => $query
                ->where('status', 'active')
                ->where(fn ($query) => $query->whereNull('sale_starts_at')->orWhere('sale_starts_at', '<=', now()))
                ->where(fn ($query) => $query->whereNull('sale_ends_at')->orWhere('sale_ends_at', '>=', now()))])
            ->orderBy('starts_at')
            ->get();

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
        ]);

        $availableTicketTypeIds = $event->ticketTypes->pluck('id');
        $event->setRelation('coupons', $event->coupons->filter(
            fn ($coupon) => $coupon->ticket_type_id === null || $availableTicketTypeIds->contains($coupon->ticket_type_id)
        )->values());

        return view('events.show', compact('event'));
    }

    public function profile(Request $request): View
    {
        $orders = TicketOrder::query()
            ->with(['items.event', 'items.ticketType', 'tickets.event', 'tickets.ticketType'])
            ->when($request->user(), fn ($query) => $query->where('user_id', $request->user()->id))
            ->latest()
            ->get();

        return view('profile', compact('orders'));
    }

    public function lookup(Request $request): View
    {
        $orders = collect();

        if ($request->filled(['phone', 'order_number'])) {
            $orders = TicketOrder::query()
                ->with(['items.event', 'items.ticketType', 'tickets.event', 'tickets.ticketType'])
                ->where('customer_phone', $request->string('phone'))
                ->where('order_number', strtoupper((string) $request->string('order_number')))
                ->latest()
                ->get();
        }

        return view('orders.lookup', compact('orders'));
    }
}
