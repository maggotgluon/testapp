<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Coupon extends Model
{
    protected $fillable = [
        'event_id',
        'name',
        'code',
        'discount_type',
        'discount_value',
        'usage_limit',
        'used_count',
        'starts_at',
        'expires_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function isValidFor(int $subtotal): bool
    {
        return $this->is_active
            && ($this->usage_limit === null || $this->used_count < $this->usage_limit)
            && ($this->starts_at === null || $this->starts_at->isPast())
            && ($this->expires_at === null || $this->expires_at->isFuture())
            && $subtotal > 0;
    }

    public function discountFor(int $subtotal): int
    {
        if (! $this->isValidFor($subtotal)) {
            return 0;
        }

        if ($this->discount_type === 'percent') {
            return min($subtotal, (int) round($subtotal * ($this->discount_value / 100)));
        }

        return min($subtotal, $this->discount_value);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
