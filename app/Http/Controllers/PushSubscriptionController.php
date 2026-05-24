<?php

namespace App\Http\Controllers;

use App\Services\CrmSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function store(Request $request, CrmSyncService $crm): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'string', 'max:500'],
            'keys' => ['required', 'array'],
            'keys.p256dh' => ['required', 'string'],
            'keys.auth' => ['required', 'string'],
            'contentEncoding' => ['nullable', 'string', 'max:50'],
        ]);

        $request->user()->updatePushSubscription(
            $data['endpoint'],
            $data['keys']['p256dh'],
            $data['keys']['auth'],
            $data['contentEncoding'] ?? 'aes128gcm'
        );

        $crm->pushCustomerActivity($request->user()->fresh(), 'web_push_enabled');

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request, CrmSyncService $crm): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'string', 'max:500'],
        ]);

        $request->user()->deletePushSubscription($data['endpoint']);
        $crm->pushCustomerActivity($request->user()->fresh(), 'web_push_disabled');

        return response()->json(['ok' => true]);
    }
}
