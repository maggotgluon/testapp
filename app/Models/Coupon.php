<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Coupon extends Model
{
    protected $fillable = [
        'event_id',
        'ticket_type_id',
        'name',
        'code',
        'discount_type',
        'discount_scope',
        'discount_value',
        'usage_limit',
        'used_count',
        'starts_at',
        'expires_at',
        'is_active',
        'show_on_checkout',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
            'show_on_checkout' => 'boolean',
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

    public function eligibleSubtotal($items, $ticketTypes): int
    {
        return $items->sum(function ($item) use ($ticketTypes) {
            $ticketType = $ticketTypes[(int) $item['ticket_type_id']] ?? null;

            if (! $ticketType || ($this->ticket_type_id && $this->ticket_type_id !== $ticketType->id)) {
                return 0;
            }

            return $ticketType->price_thb * (int) $item['quantity'];
        });
    }

    public function discountForItems($items, $ticketTypes): int
    {
        $eligibleSubtotal = $this->eligibleSubtotal($items, $ticketTypes);

        if (! $this->isValidFor($eligibleSubtotal)) {
            return 0;
        }

        if ($this->discount_scope === 'item') {
            return $items->sum(function ($item) use ($ticketTypes) {
                $ticketType = $ticketTypes[(int) $item['ticket_type_id']] ?? null;

                if (! $ticketType || ($this->ticket_type_id && $this->ticket_type_id !== $ticketType->id)) {
                    return 0;
                }

                $lineTotal = $ticketType->price_thb * (int) $item['quantity'];

                if ($this->discount_type === 'percent') {
                    return min($lineTotal, (int) round($lineTotal * ($this->discount_value / 100)));
                }

                return min($lineTotal, $this->discount_value * (int) $item['quantity']);
            });
        }

        return $this->discountFor($eligibleSubtotal);
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

    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }
}
