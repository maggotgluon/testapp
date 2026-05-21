<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketType extends Model
{
    protected $fillable = [
        'event_id',
        'name',
        'description',
        'price_thb',
        'capacity',
        'sold_count',
        'sale_starts_at',
        'sale_ends_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'sale_starts_at' => 'datetime',
            'sale_ends_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function availableQuantity(): int
    {
        if ($this->capacity === 0) {
            return 9999;
        }

        return max(0, $this->capacity - $this->sold_count);
    }

    public function isOnSale(): bool
    {
        return $this->status === 'active'
            && ($this->sale_starts_at === null || $this->sale_starts_at->isPast())
            && ($this->sale_ends_at === null || $this->sale_ends_at->isFuture())
            && $this->availableQuantity() > 0;
    }
}
