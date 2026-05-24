<?php

namespace App\Http\Controllers;

use App\Models\TicketOrder;
use App\Models\User;
use App\Services\CrmSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmController extends Controller
{
    public function lookupCustomer(Request $request, CrmSyncService $crm): JsonResponse
    {
        $this->authorizeCrm($request);

        $data = $request->validate([
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email'],
            'line_user_id' => ['nullable', 'string', 'max:255'],
            'ticket_app_user_id' => ['nullable', 'integer'],
        ]);

        abort_if(collect($data)->filter()->isEmpty(), 422, 'At least one lookup key is required.');

        $user = User::query()
            ->with(['orders.items.event', 'orders.tickets.event', 'pushSubscriptions'])
            ->when($data['ticket_app_user_id'] ?? null, fn ($query, $id) => $query->whereKey($id))
            ->when($data['phone'] ?? null, fn ($query, $phone) => $query->orWhere('phone', $phone))
            ->when($data['email'] ?? null, fn ($query, $email) => $query->orWhere('email', $email))
            ->when($data['line_user_id'] ?? null, fn ($query, $lineUserId) => $query->orWhere(fn ($query) => $query->where('provider', 'line')->where('provider_id', $lineUserId)))
            ->first();

        if (! $user) {
            return response()->json(['customer' => null], 404);
        }

        return response()->json([
            'customer' => $crm->customerPayload($user),
            'summary' => [
                'orders_count' => $user->orders->count(),
                'tickets_count' => $user->orders->sum(fn ($order) => $order->tickets->count()),
                'events_attended' => $user->orders
                    ->flatMap(fn ($order) => $order->tickets)
                    ->filter(fn ($ticket) => in_array($ticket->status, ['checked_in', 'checked_out'], true))
                    ->pluck('event.name')
                    ->filter()
                    ->unique()
                    ->values(),
            ],
        ]);
    }

    public function upsertCustomer(Request $request, CrmSyncService $crm): JsonResponse
    {
        $this->authorizeCrm($request);

        $data = $request->validate([
            'customer' => ['required', 'array'],
            'customer.ticket_app_user_id' => ['nullable', 'integer'],
            'customer.name' => ['nullable', 'string', 'max:255'],
            'customer.phone' => ['nullable', 'string', 'max:40'],
            'customer.email' => ['nullable', 'email'],
            'customer.line_user_id' => ['nullable', 'string', 'max:255'],
            'customer.avatar' => ['nullable', 'url', 'max:2048'],
            'customer.line_picture_url' => ['nullable', 'url', 'max:2048'],
            'customer.line_friend_status' => ['nullable', 'string', 'max:40'],
        ]);

        $customer = $data['customer'];
        abort_if(collect($customer)->only(['ticket_app_user_id', 'phone', 'email', 'line_user_id'])->filter()->isEmpty(), 422, 'At least one customer identifier is required.');
        $user = User::query()
            ->when($customer['ticket_app_user_id'] ?? null, fn ($query, $id) => $query->whereKey($id))
            ->when($customer['line_user_id'] ?? null, fn ($query, $lineUserId) => $query->orWhere(fn ($query) => $query->where('provider', 'line')->where('provider_id', $lineUserId)))
            ->when($customer['phone'] ?? null, fn ($query, $phone) => $query->orWhere('phone', $phone))
            ->when($customer['email'] ?? null, fn ($query, $email) => $query->orWhere('email', $email))
            ->first();

        $user ??= User::create([
            'name' => $customer['name'] ?? 'CRM Customer',
            'phone' => $customer['phone'] ?? null,
            'email' => $customer['email'] ?? null,
            'provider' => ! empty($customer['line_user_id']) ? 'line' : 'guest',
            'provider_id' => $customer['line_user_id'] ?? 'crm-'.uniqid(),
            'role' => 'customer',
        ]);

        $crm->applyCustomerToUser($user, $customer);

        return response()->json([
            'ok' => true,
            'customer' => $crm->customerPayload($user->fresh()),
        ]);
    }

    public function showOrder(Request $request, TicketOrder $order, CrmSyncService $crm): JsonResponse
    {
        $this->authorizeCrm($request);

        $order->load(['user.pushSubscriptions', 'items.event', 'items.ticketType', 'tickets.event', 'tickets.ticketType', 'coupon']);

        return response()->json([
            'customer' => $order->user ? $crm->customerPayload($order->user) : [
                'name' => $order->customer_name,
                'phone' => $order->customer_phone,
                'email' => $order->customer_email,
            ],
            'order' => $crm->orderPayload($order),
        ]);
    }

    private function authorizeCrm(Request $request): void
    {
        $token = config('services.crm.webhook_token');

        abort_unless(filled($token), 403);

        $given = $request->bearerToken() ?: $request->header('X-CRM-Token');

        abort_unless(hash_equals($token, (string) $given), 403);
    }
}
