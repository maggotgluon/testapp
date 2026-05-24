<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CrmSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LineWebhookController extends Controller
{
    public function __invoke(Request $request, CrmSyncService $crm): JsonResponse
    {
        $secret = config('services.line.messaging_channel_secret');
        $body = $request->getContent();

        if ($secret) {
            $signature = base64_encode(hash_hmac('sha256', $body, $secret, true));

            if (! hash_equals($signature, (string) $request->header('X-Line-Signature'))) {
                return response()->json(['message' => 'Invalid signature'], 403);
            }
        }

        foreach ($request->input('events', []) as $event) {
            $lineUserId = $event['source']['userId'] ?? null;

            if (! $lineUserId) {
                continue;
            }

            if ($event['type'] === 'follow') {
                User::where('provider', 'line')
                    ->where('provider_id', $lineUserId)
                    ->update([
                        'line_friend_status' => 'followed',
                        'line_followed_at' => now(),
                        'line_blocked_at' => null,
                    ]);

                if ($user = User::where('provider', 'line')->where('provider_id', $lineUserId)->first()) {
                    $crm->pushCustomerActivity($user, 'line_followed');
                }
            }

            if ($event['type'] === 'unfollow') {
                User::where('provider', 'line')
                    ->where('provider_id', $lineUserId)
                    ->update([
                        'line_friend_status' => 'blocked',
                        'line_blocked_at' => now(),
                    ]);

                if ($user = User::where('provider', 'line')->where('provider_id', $lineUserId)->first()) {
                    $crm->pushCustomerActivity($user, 'line_blocked');
                }
            }
        }

        Log::info('LINE webhook processed', ['events' => count($request->input('events', []))]);

        return response()->json(['ok' => true]);
    }
}
