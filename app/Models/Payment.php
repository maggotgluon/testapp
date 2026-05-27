<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'ticket_order_id',
        'method',
        'amount_thb',
        'status',
        'slip_path',
        'note',
        'slip_qr_status',
        'slip_qr_payload',
        'slip_qr_data',
        'slip_qr_amount_thb',
        'slip_qr_paid_at',
        'slip_qr_reference',
        'slip_qr_receiver',
    ];

    protected $casts = [
        'slip_qr_data' => 'array',
        'slip_qr_amount_thb' => 'decimal:2',
        'slip_qr_paid_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(TicketOrder::class, 'ticket_order_id');
    }
}
