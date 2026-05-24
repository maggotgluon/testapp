<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LineMessagingService
{
    public function available(): bool
    {
        return filled(config('services.line.messaging_channel_access_token'));
    }

    public function isConfigured(): bool
    {
        return filled(config('services.line.messaging_channel_access_token'))
            && filled(config('services.line.messaging_channel_secret'));
    }

    public function pushText(User $user, string $message): bool
    {
        if (! $this->available() || $user->provider !== 'line' || blank($user->provider_id)) {
            return false;
        }

        $response = Http::withToken(config('services.line.messaging_channel_access_token'))
            ->acceptJson()
            ->post('https://api.line.me/v2/bot/message/push', [
                'to' => $user->provider_id,
                'messages' => [
                    [
                        'type' => 'text',
                        'text' => mb_substr($message, 0, 5000),
                    ],
                ],
            ]);

        if ($response->failed()) {
            Log::warning('LINE push message failed', [
                'user_id' => $user->id,
                'line_user_id' => $user->provider_id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        }

        return true;
    }
}
