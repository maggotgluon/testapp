<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    protected $fillable = [
        'created_by',
        'name',
        'description',
        'venue',
        'location',
        'hosted_by',
        'starts_at',
        'ends_at',
        'poster_path',
        'ticket_image_path',
        'bank_name',
        'bank_account_name',
        'bank_account_number',
        'qr_payment_account_name',
        'qr_payment_account',
        'qr_payment_image_path',
        'payment_instructions',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_published' => 'boolean',
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

    public function scopeVisible($query)
    {
        return $query->where('is_published', true)->where('ends_at', '>=', now());
    }
}
