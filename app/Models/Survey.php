<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Survey extends Model
{
    public const PLACEMENTS = [
        'before_event_view' => 'Before event view / ก่อนดูอีเวนต์',
        'before_ticket_selection' => 'Before ticket selection / ก่อนเลือกตั๋ว',
        'before_payment' => 'Before proceed to payment / ก่อนชำระเงิน',
        'before_free_order_approval' => 'Before free ticket approval / ก่อนอนุมัติตั๋วฟรี',
        'free_ticket_gate' => 'Free ticket gate / รับตั๋วฟรีผ่านแบบสอบถาม',
        'after_payment' => 'After payment/order / หลังชำระเงินหรือส่งออเดอร์',
        'on_login' => 'When login / ตอนเข้าสู่ระบบ',
    ];

    protected $fillable = [
        'event_id',
        'created_by',
        'title',
        'description',
        'description_format',
        'placement',
        'questions',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'questions' => 'array',
            'is_active' => 'boolean',
            'description_format' => 'string',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }

    public function scopeForPlacement(Builder $query, string $placement, ?int $eventId = null): Builder
    {
        return $query
            ->where('placement', $placement)
            ->where(fn ($query) => $query->whereNull('event_id')->when($eventId, fn ($query) => $query->orWhere('event_id', $eventId)));
    }

    public function placementLabel(): string
    {
        return self::PLACEMENTS[$this->placement] ?? $this->placement;
    }
}
