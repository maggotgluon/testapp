<?php

namespace App\Services;

use App\Models\Event;
use App\Models\TicketOrder;

class TicketingAiAssistant
{
    public function dashboardInsights(): array
    {
        $pending = TicketOrder::where('status', 'pending')->count();
        $nextEvent = Event::visible()->orderBy('starts_at')->with('ticketTypes')->first();
        $insights = [];

        if ($pending > 0) {
            $insights[] = "Prioritize {$pending} pending payment review(s) so customers receive active tickets before event day.";
        }

        if ($nextEvent) {
            $lowInventory = $nextEvent->ticketTypes
                ->filter(fn ($ticket) => $ticket->capacity > 0 && $ticket->availableQuantity() <= max(5, (int) floor($ticket->capacity * 0.1)))
                ->pluck('name')
                ->join(', ');

            $insights[] = "Next event is {$nextEvent->name} on {$nextEvent->starts_at->format('M j')}. Keep scanner staff assigned before doors open.";

            if ($lowInventory !== '') {
                $insights[] = "Low inventory detected for {$lowInventory}. Consider closing ads or raising final-batch pricing.";
            }
        }

        if ($insights === []) {
            $insights[] = 'No urgent action detected. Add an event or monitor pending orders as sales begin.';
        }

        return $insights;
    }
}
