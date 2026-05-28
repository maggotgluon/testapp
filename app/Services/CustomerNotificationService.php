<?php

namespace App\Services;

use App\Models\Event;
use App\Models\TicketOrder;
use App\Models\User;
use App\Notifications\CustomerWebPushNotification;
use Illuminate\Support\Collection;

class CustomerNotificationService
{
    public function __construct(private LineMessagingService $line)
    {
    }

    public function orderApproved(TicketOrder $order): array
    {
        $order->loadMissing(['user', 'items.event']);

        if (! $order->user) {
            return ['line' => 0, 'web_push' => 0];
        }

        $eventName = $order->items->pluck('event.name')->filter()->unique()->join(', ');
        $url = route('orders.show', $order);
        $message = "Your ticket order {$order->order_number} is approved.\nออเดอร์ {$order->order_number} ได้รับการอนุมัติแล้ว\n{$eventName}\n{$url}";

        return $this->sendToUsers(
            collect([$order->user]),
            'Ticket approved / ตั๋วได้รับการอนุมัติ',
            $message,
            $url,
            ['line', 'web_push']
        );
    }

    public function orderCreatedForAdmins(TicketOrder $order): array
    {
        $order->loadMissing(['items.event']);

        $eventIds = $order->items->pluck('event_id')->filter()->unique()->values();
        $eventName = $order->items->pluck('event.name')->filter()->unique()->join(', ');
        $url = route('admin.orders.show', $order);
        $message = "New ticket order {$order->order_number} is waiting for review.\nมีออเดอร์ใหม่ {$order->order_number} รอตรวจสอบ\n{$eventName}\n{$url}";

        $users = User::query()
            ->whereIn('role', ['super_admin', 'event_admin'])
            ->where(function ($query) use ($eventIds) {
                $query->where('role', 'super_admin')
                    ->orWhereHas('assignedEvents', fn ($assigned) => $assigned->whereIn('events.id', $eventIds));
            })
            ->get();

        return $this->sendToUsers(
            $users,
            'New order / ออเดอร์ใหม่',
            $message,
            $url,
            ['web_push']
        );
    }

    public function eventMessage(Event $event, Collection $users, string $subject, string $message, array $channels): array
    {
        $body = trim($subject)."\n\n".trim($message)."\n\n".$event->name."\n".route('events.show', $event);

        return $this->sendToUsers(
            $users,
            $subject,
            $body,
            route('events.show', $event),
            $channels
        );
    }

    public function availableChannels(): array
    {
        return array_values(array_filter([
            $this->line->isConfigured() ? 'line' : null,
            $this->webPushConfigured() ? 'web_push' : null,
        ]));
    }

    public function webPushConfigured(): bool
    {
        return filled(config('webpush.vapid.public_key'))
            && filled(config('webpush.vapid.private_key'));
    }

    private function sendToUsers(Collection $users, string $title, string $body, string $url, array $channels): array
    {
        $channels = array_values(array_intersect($channels, $this->availableChannels()));
        $counts = ['line' => 0, 'web_push' => 0];

        foreach ($users->unique('id') as $user) {
            if (in_array('line', $channels, true) && $this->line->pushText($user, $body)) {
                $counts['line']++;
            }

            if (in_array('web_push', $channels, true) && $user->pushSubscriptions()->exists()) {
                $user->notify(new CustomerWebPushNotification($title, $body, $url));
                $counts['web_push']++;
            }
        }

        return $counts;
    }
}
