<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketOrder;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CrmSyncService
{
    public function enabled(): bool
    {
        return filled(config('services.crm.base_url')) && filled(config('services.crm.token'));
    }

    public function pullCustomer(array $identifiers): ?array
    {
        if (! $this->enabled()) {
            return null;
        }

        try {
            $response = Http::withToken(config('services.crm.token'))
                ->acceptJson()
                ->timeout(5)
                ->get(rtrim(config('services.crm.base_url'), '/').'/customers/lookup', array_filter([
                    'phone' => $identifiers['phone'] ?? null,
                    'email' => $identifiers['email'] ?? null,
                    'line_user_id' => $identifiers['line_user_id'] ?? null,
                    'external_id' => $identifiers['external_id'] ?? null,
                ]));

            if ($response->failed()) {
                return null;
            }

            return $response->json('customer') ?? $response->json();
        } catch (\Throwable $exception) {
            Log::warning('CRM customer pull failed', ['message' => $exception->getMessage()]);

            return null;
        }
    }

    public function applyCustomerToUser(User $user, array $customer): User
    {
        $updates = [
            'name' => $customer['name'] ?? $user->name,
            'phone' => $customer['phone'] ?? $user->phone,
            'email' => $customer['email'] ?? $user->email,
            'avatar' => $customer['avatar'] ?? $customer['line_picture_url'] ?? $user->avatar,
        ];

        if (! empty($customer['line_user_id'])) {
            $updates['provider'] = 'line';
            $updates['provider_id'] = $customer['line_user_id'];
            $updates['line_friend_status'] = $customer['line_friend_status'] ?? $user->line_friend_status;
        }

        $user->update(array_filter($updates, fn ($value) => $value !== null));

        return $user->refresh();
    }

    public function pushCustomer(User $user, string $source = 'ticket_app'): bool
    {
        return $this->post('/customers/upsert', [
            'source' => $source,
            'customer' => $this->customerPayload($user),
        ]);
    }

    public function pushOrderActivity(TicketOrder $order, string $type): bool
    {
        $order->loadMissing(['user', 'items.event', 'items.ticketType', 'tickets.event', 'tickets.ticketType', 'coupon']);

        return $this->post('/customer-activities', [
            'type' => $type,
            'occurred_at' => now()->toIso8601String(),
            'customer' => $order->user ? $this->customerPayload($order->user) : [
                'name' => $order->customer_name,
                'phone' => $order->customer_phone,
                'email' => $order->customer_email,
            ],
            'order' => $this->orderPayload($order),
        ]);
    }

    public function pushTicketActivity(Ticket $ticket, string $type): bool
    {
        $ticket->loadMissing(['event', 'ticketType', 'order.user']);

        return $this->post('/customer-activities', [
            'type' => $type,
            'occurred_at' => now()->toIso8601String(),
            'customer' => $ticket->order?->user ? $this->customerPayload($ticket->order->user) : [
                'name' => $ticket->holder_name,
                'phone' => $ticket->holder_phone,
                'email' => $ticket->order?->customer_email,
            ],
            'ticket' => $this->ticketPayload($ticket),
            'order' => $ticket->order ? $this->orderPayload($ticket->order) : null,
        ]);
    }

    public function pushCustomerActivity(User $user, string $type, array $metadata = []): bool
    {
        return $this->post('/customer-activities', [
            'type' => $type,
            'occurred_at' => now()->toIso8601String(),
            'customer' => $this->customerPayload($user),
            'metadata' => $metadata,
        ]);
    }

    public function customerPayload(User $user): array
    {
        return [
            'ticket_app_user_id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'phone' => $user->phone,
            'email' => $user->email,
            'provider' => $user->provider,
            'line_user_id' => $user->provider === 'line' ? $user->provider_id : null,
            'avatar' => $user->avatar,
            'line_friend_status' => $user->line_friend_status,
            'line_followed_at' => $user->line_followed_at?->toIso8601String(),
            'line_blocked_at' => $user->line_blocked_at?->toIso8601String(),
            'web_push_enabled' => $user->relationLoaded('pushSubscriptions')
                ? $user->pushSubscriptions->isNotEmpty()
                : $user->pushSubscriptions()->exists(),
            'updated_at' => $user->updated_at?->toIso8601String(),
        ];
    }

    public function orderPayload(TicketOrder $order): array
    {
        return [
            'ticket_app_order_id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'subtotal_thb' => $order->subtotal_thb,
            'discount_thb' => $order->discount_thb,
            'total_thb' => $order->total_thb,
            'payment_method' => $order->payment_method,
            'coupon_code' => $order->coupon?->code,
            'created_at' => $order->created_at?->toIso8601String(),
            'approved_at' => $order->approved_at?->toIso8601String(),
            'events' => $order->items->pluck('event')->filter()->unique('id')->map(fn (Event $event) => $this->eventPayload($event))->values()->all(),
            'items' => $order->items->map(fn ($item) => [
                'event_id' => $item->event_id,
                'event_name' => $item->event?->name,
                'ticket_type_id' => $item->ticket_type_id,
                'ticket_type_name' => $item->ticketType?->name,
                'quantity' => $item->quantity,
                'unit_price_thb' => $item->unit_price_thb,
                'line_total_thb' => $item->line_total_thb,
            ])->values()->all(),
            'tickets' => $order->tickets->map(fn (Ticket $ticket) => $this->ticketPayload($ticket))->values()->all(),
        ];
    }

    public function ticketPayload(Ticket $ticket): array
    {
        return [
            'ticket_app_ticket_id' => $ticket->id,
            'uuid' => $ticket->uuid,
            'status' => $ticket->status,
            'holder_name' => $ticket->holder_name,
            'holder_phone' => $ticket->holder_phone,
            'event' => $ticket->event ? $this->eventPayload($ticket->event) : null,
            'ticket_type' => [
                'id' => $ticket->ticketType?->id,
                'name' => $ticket->ticketType?->name,
                'price_thb' => $ticket->ticketType?->price_thb,
                'full_price_thb' => $ticket->ticketType?->full_price_thb,
            ],
            'checked_in_at' => $ticket->checked_in_at?->toIso8601String(),
            'checked_out_at' => $ticket->checked_out_at?->toIso8601String(),
        ];
    }

    public function eventPayload(Event $event): array
    {
        return [
            'ticket_app_event_id' => $event->id,
            'name' => $event->name,
            'venue' => $event->venue,
            'location' => $event->location,
            'location_url' => $event->location_url,
            'hosted_by' => $event->hosted_by,
            'hosted_by_url' => $event->hosted_by_url,
            'starts_at' => $event->starts_at?->toIso8601String(),
            'ends_at' => $event->ends_at?->toIso8601String(),
        ];
    }

    private function post(string $path, array $payload): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        try {
            $response = Http::withToken(config('services.crm.token'))
                ->acceptJson()
                ->timeout(5)
                ->post(rtrim(config('services.crm.base_url'), '/').$path, $payload);

            if ($response->failed()) {
                Log::warning('CRM sync failed', [
                    'path' => $path,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $exception) {
            Log::warning('CRM sync exception', [
                'path' => $path,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}
