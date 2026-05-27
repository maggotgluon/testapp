<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class Promotion extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'ticket_type_id',
        'name',
        'description',
        'promotion_type',
        'discount_scope',
        'buy_quantity',
        'get_quantity',
        'min_quantity',
        'discount_value',
        'max_discount_thb',
        'usage_limit',
        'used_count',
        'starts_at',
        'expires_at',
        'combines_with_coupons',
        'is_active',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'combines_with_coupons' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }

    public function isValidFor(bool $hasCoupon = false): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($hasCoupon && ! $this->combines_with_coupons) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    public function eligibleQuantity(Collection $items, Collection $ticketTypes): int
    {
        return $items->sum(function (array $item) use ($ticketTypes): int {
            $ticketType = $ticketTypes->get((int) $item['ticket_type_id']);

            if (! $ticketType || $this->ticketTypeDoesNotMatch($ticketType)) {
                return 0;
            }

            return (int) $item['quantity'];
        });
    }

    public function eligibleSubtotal(Collection $items, Collection $ticketTypes): int
    {
        return $items->sum(function (array $item) use ($ticketTypes): int {
            $ticketType = $ticketTypes->get((int) $item['ticket_type_id']);

            if (! $ticketType || $this->ticketTypeDoesNotMatch($ticketType)) {
                return 0;
            }

            return $ticketType->price_thb * (int) $item['quantity'];
        });
    }

    public function discountForItems(Collection $items, Collection $ticketTypes, bool $hasCoupon = false): int
    {
        if (! $this->isValidFor($hasCoupon)) {
            return 0;
        }

        $eligibleQuantity = $this->eligibleQuantity($items, $ticketTypes);
        $minQuantity = max(1, (int) ($this->min_quantity ?: 1));

        if ($eligibleQuantity < $minQuantity) {
            return 0;
        }

        $discount = match ($this->promotion_type) {
            'buy_x_get_y' => $this->buyXGetYDiscount($items, $ticketTypes, $eligibleQuantity),
            'percent' => $this->percentageDiscount($items, $ticketTypes),
            'fixed' => $this->fixedDiscount($items, $ticketTypes),
            default => 0,
        };

        if ($this->max_discount_thb !== null) {
            $discount = min($discount, $this->max_discount_thb);
        }

        return min($this->eligibleSubtotal($items, $ticketTypes), $discount);
    }

    public function displaySummary(): string
    {
        return match ($this->promotion_type) {
            'buy_x_get_y' => 'Buy '.(int) $this->buy_quantity.' get '.(int) $this->get_quantity.' free',
            'percent' => (int) $this->discount_value.'% off',
            'fixed' => 'THB '.number_format((int) $this->discount_value).' off',
            default => 'Promotion',
        };
    }

    private function buyXGetYDiscount(Collection $items, Collection $ticketTypes, int $eligibleQuantity): int
    {
        $buyQuantity = max(1, (int) $this->buy_quantity);
        $getQuantity = max(1, (int) $this->get_quantity);
        $groupSize = $buyQuantity + $getQuantity;
        $freeQuantity = intdiv($eligibleQuantity, $groupSize) * $getQuantity;

        if ($freeQuantity < 1) {
            return 0;
        }

        $unitPrices = collect();
        foreach ($items as $item) {
            $ticketType = $ticketTypes->get((int) $item['ticket_type_id']);

            if (! $ticketType || $this->ticketTypeDoesNotMatch($ticketType)) {
                continue;
            }

            for ($i = 0; $i < (int) $item['quantity']; $i++) {
                $unitPrices->push($ticketType->price_thb);
            }
        }

        return $unitPrices->sort()->take($freeQuantity)->sum();
    }

    private function percentageDiscount(Collection $items, Collection $ticketTypes): int
    {
        if ($this->discount_scope === 'item') {
            return $items->sum(function (array $item) use ($ticketTypes): int {
                $ticketType = $ticketTypes->get((int) $item['ticket_type_id']);

                if (! $ticketType || $this->ticketTypeDoesNotMatch($ticketType)) {
                    return 0;
                }

                $lineTotal = $ticketType->price_thb * (int) $item['quantity'];

                return (int) round($lineTotal * ((int) $this->discount_value / 100));
            });
        }

        return (int) round($this->eligibleSubtotal($items, $ticketTypes) * ((int) $this->discount_value / 100));
    }

    private function fixedDiscount(Collection $items, Collection $ticketTypes): int
    {
        if ($this->discount_scope === 'item') {
            return $this->eligibleQuantity($items, $ticketTypes) * (int) $this->discount_value;
        }

        return (int) $this->discount_value;
    }

    private function ticketTypeDoesNotMatch(TicketType $ticketType): bool
    {
        return ($this->event_id && (int) $this->event_id !== (int) $ticketType->event_id)
            || ($this->ticket_type_id && (int) $this->ticket_type_id !== (int) $ticketType->id);
    }
}
