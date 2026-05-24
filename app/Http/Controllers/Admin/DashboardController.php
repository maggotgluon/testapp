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

        $eventQuery = Event::query()
            ->when($assignedEventIds !== null, fn ($query) => $query->whereIn('id', $assignedEventIds));
        $orderQuery = TicketOrder::query()
            ->when($assignedEventIds !== null, fn ($query) => $query->whereHas('items', fn ($items) => $items->whereIn('event_id', $assignedEventIds)));
        $ticketQuery = Ticket::query()
            ->when($assignedEventIds !== null, fn ($query) => $query->whereIn('event_id', $assignedEventIds));

        return view('admin.dashboard', [
            'eventCount' => (clone $eventQuery)->count(),
            'pendingOrders' => (clone $orderQuery)->where('status', 'pending')->count(),
            'revenueThb' => (clone $orderQuery)->where('status', 'approved')->sum('total_thb'),
            'checkedIn' => (clone $ticketQuery)->where('status', 'checked_in')->count(),
            'recentOrders' => (clone $orderQuery)->withCount('tickets')->latest()->limit(8)->get(),
            'aiInsights' => $assistant->dashboardInsights(),
        ]);
    }
}
