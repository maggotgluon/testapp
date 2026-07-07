<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class BeamService
{
    protected string $baseUrl;

    protected string $merchantId;

    protected string $apiKey;

    protected string $webhookKey;

    public function __construct()
    {
        $config = config('services.beam');
        $this->merchantId = $config['merchant_id'] ?? '';
        $this->apiKey = $config['api_key'] ?? '';
        $this->webhookKey = $config['webhook_key'] ?? '';
        $environment = $config['environment'] ?? 'playground';
        $this->baseUrl = $environment === 'production'
            ? 'https://api.beamcheckout.com'
            : 'https://playground.api.beamcheckout.com';
    }

    protected function client(): PendingRequest
    {
        return Http::withBasicAuth($this->merchantId, $this->apiKey)
            ->acceptJson()
            ->throw();
    }

    public function createCharge(array $params): array
    {
        $response = $this->client()->post($this->baseUrl.'/api/v1/charges', $params);

        return $response->json();
    }

    public function getCharge(string $chargeId): array
    {
        $response = $this->client()->get($this->baseUrl.'/api/v1/charges/'.$chargeId);

        return $response->json();
    }

    public function refundCharge(string $chargeId): array
    {
        $response = $this->client()->post($this->baseUrl.'/api/v1/charges/'.$chargeId.'/refund');

        return $response->json();
    }

    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        if (! $this->webhookKey) {
            return false;
        }

        $decodedKey = base64_decode($this->webhookKey);
        $expected = base64_encode(
            hash_hmac('sha256', $payload, $decodedKey, true)
        );

        return hash_equals($expected, $signature);
    }
}
