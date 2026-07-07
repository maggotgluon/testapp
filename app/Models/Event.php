<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    protected $fillable = [
        'created_by',
        'name',
        'description',
        'description_format',
        'social_description',
        'venue',
        'location',
        'location_url',
        'hosted_by',
        'hosted_by_url',
        'starts_at',
        'ends_at',
        'poster_path',
        'ticket_image_path',
        'social_image_path',
        'bank_name',
        'bank_account_name',
        'bank_account_number',
        'qr_payment_account_name',
        'qr_payment_account',
        'qr_payment_image_path',
        'payment_instructions',
        'payment_methods',
        'payment_accounts',
        'beam_enabled',
        'beam_fee_behavior',
        'beam_fee_percent',
        'is_published',
        'show_countdown',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'payment_methods' => 'array',
            'payment_accounts' => 'array',
            'is_published' => 'boolean',
            'show_countdown' => 'boolean',
            'beam_enabled' => 'boolean',
            'beam_fee_percent' => 'decimal:2',
        ];
    }

    public function enabledPaymentMethods(): array
    {
        $accountMethods = collect($this->paymentOptions())
            ->where('is_active', true)
            ->pluck('method')
            ->all();
        $methods = $accountMethods ?: (is_array($this->payment_methods) ? $this->payment_methods : ['qr_payment', 'bank_transfer']);
        $enabled = array_values(array_intersect($methods, ['qr_payment', 'bank_transfer', 'cash']));

        if ($this->beam_enabled) {
            $enabled[] = 'beam';
        }

        return $enabled ?: ['qr_payment'];
    }

    public function paymentOptions(): array
    {
        $accounts = collect(is_array($this->payment_accounts) ? $this->payment_accounts : [])
            ->filter(fn ($account) => is_array($account) && in_array($account['method'] ?? null, ['qr_payment', 'bank_transfer', 'cash'], true))
            ->map(fn ($account, $index) => $this->normalizePaymentAccount($account, (string) $index))
            ->values()
            ->all();

        if ($accounts !== []) {
            return $accounts;
        }

        $legacyMethods = is_array($this->payment_methods) ? $this->payment_methods : ['qr_payment', 'bank_transfer'];

        return collect([
            [
                'key' => 'qr-payment',
                'method' => 'qr_payment',
                'label' => $this->qr_payment_account_name ?: 'QR payment / ชำระด้วย QR',
                'account_name' => $this->qr_payment_account_name,
                'account_number' => $this->qr_payment_account,
                'instructions' => $this->payment_instructions,
                'is_active' => in_array('qr_payment', $legacyMethods, true),
            ],
            [
                'key' => 'bank-transfer',
                'method' => 'bank_transfer',
                'label' => $this->bank_name ?: 'Bank transfer / โอนธนาคาร',
                'bank_name' => $this->bank_name,
                'account_name' => $this->bank_account_name,
                'account_number' => $this->bank_account_number,
                'instructions' => $this->payment_instructions,
                'is_active' => in_array('bank_transfer', $legacyMethods, true),
            ],
            [
                'key' => 'cash',
                'method' => 'cash',
                'label' => 'Cash sale / เงินสด',
                'account_name' => null,
                'account_number' => null,
                'instructions' => $this->payment_instructions,
                'is_active' => in_array('cash', $legacyMethods, true),
            ],
        ])->filter(fn ($account) => $account['is_active'])->values()->all();
    }

    public function paymentOption(?string $key, ?string $method = null): ?array
    {
        return collect($this->paymentOptions())
            ->first(fn ($account) => ($key && ($account['key'] ?? null) === $key) || ($method && ($account['method'] ?? null) === $method));
    }

    private function normalizePaymentAccount(array $account, string $fallbackKey): array
    {
        $method = $account['method'] ?? 'qr_payment';
        $key = $account['key'] ?? str($method.'-'.$fallbackKey)->slug()->toString();

        return [
            'key' => $key,
            'method' => $method,
            'label' => $account['label'] ?? match ($method) {
                'bank_transfer' => 'Bank transfer / โอนธนาคาร',
                'cash' => 'Cash sale / เงินสด',
                default => 'QR payment / ชำระด้วย QR',
            },
            'bank_name' => $account['bank_name'] ?? null,
            'account_name' => $account['account_name'] ?? null,
            'account_number' => $account['account_number'] ?? null,
            'instructions' => $account['instructions'] ?? null,
            'is_active' => (bool) ($account['is_active'] ?? true),
        ];
    }

    public function ticketTypes(): HasMany
    {
        return $this->hasMany(TicketType::class);
    }

    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class);
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class);
    }

    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function beamFeePercent(): ?float
    {
        $fee = $this->beam_fee_percent;

        if ($fee === null || $fee === '' || (float) $fee <= 0) {
            $fee = config('services.beam.default_fee_percent', 3.0);
        }

        return (float) $fee > 0 ? (float) $fee : null;
    }

    public function scopeVisible($query)
    {
        return $query->where('is_published', true)->where('ends_at', '>=', now());
    }
}
