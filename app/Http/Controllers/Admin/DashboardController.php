<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketOrder;
use App\Services\TicketingAiAssistant;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, TicketingAiAssistant $assistant): View
    {
        $assignedEventIds = $request->user()->role === 'super_admin'
            ? null
            : $request->user()->assignedEvents()->pluck('events.id');

        $manageableEvents = Event::query()
            ->when($assignedEventIds !== null, fn ($query) => $query->whereIn('id', $assignedEventIds))
            ->orderBy('starts_at')
            ->get();
        $selectedEvent = $manageableEvents->firstWhere('id', (int) $request->integer('event_id'));
        $selectedEventId = $selectedEvent?->id;

        $eventQuery = Event::query()
            ->when($assignedEventIds !== null, fn ($query) => $query->whereIn('id', $assignedEventIds))
            ->when($selectedEventId, fn ($query) => $query->whereKey($selectedEventId));
        $orderQuery = TicketOrder::query()
            ->when($assignedEventIds !== null, fn ($query) => $query->whereHas('items', fn ($items) => $items->whereIn('event_id', $assignedEventIds)))
            ->when($selectedEventId, fn ($query) => $query->whereHas('items', fn ($items) => $items->where('event_id', $selectedEventId)));
        $ticketQuery = Ticket::query()
            ->when($assignedEventIds !== null, fn ($query) => $query->whereIn('event_id', $assignedEventIds))
            ->when($selectedEventId, fn ($query) => $query->where('event_id', $selectedEventId));

        return view('admin.dashboard', [
            'manageableEvents' => $manageableEvents,
            'selectedEvent' => $selectedEvent,
            'eventCount' => (clone $eventQuery)->count(),
            'pendingOrders' => (clone $orderQuery)->where('status', 'pending')->count(),
            'revenueThb' => (clone $orderQuery)->where('status', 'approved')->sum('total_thb'),
            'checkedIn' => (clone $ticketQuery)->where('status', 'checked_in')->count(),
            'recentOrders' => (clone $orderQuery)->with(['items.event'])->withCount('tickets')->latest()->limit(8)->get(),
            'aiInsights' => $assistant->dashboardInsights(),
        ]);
    }
}
