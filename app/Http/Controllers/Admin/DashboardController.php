<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketOrder;
use App\Services\TicketingAiAssistant;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(TicketingAiAssistant $assistant): View
    {
        return view('admin.dashboard', [
            'eventCount' => Event::count(),
            'pendingOrders' => TicketOrder::where('status', 'pending')->count(),
            'revenueThb' => TicketOrder::where('status', 'approved')->sum('total_thb'),
            'checkedIn' => Ticket::where('status', 'checked_in')->count(),
            'recentOrders' => TicketOrder::withCount('tickets')->latest()->limit(8)->get(),
            'aiInsights' => $assistant->dashboardInsights(),
        ]);
    }
}
