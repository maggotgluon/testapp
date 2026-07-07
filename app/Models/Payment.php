<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'ticket_order_id',
        'method',
        'payment_account_key',
        'payment_account_label',
        'payment_account_name',
        'payment_account_number',
        'amount_thb',
        'expected_amount_thb',
        'expected_promptpay_id',
        'status',
        'slip_path',
        'slip_archived_path',
        'slip_archived_at',
        'slip_deleted_at',
        'slip_image_sha256',
        'note',
        'beam_charge_id',
        'beam_qr_image',
        'slip_qr_status',
        'slip_qr_payload',
        'slip_qr_payload_sha256',
        'slip_qr_data',
        'slip_qr_amount_thb',
        'slip_qr_paid_at',
        'slip_qr_reference',
        'slip_qr_reference_normalized',
        'slip_qr_receiver',
        'slip_review_status',
        'slip_review_flags',
        'slip_reviewed_at',
    ];

    protected $casts = [
        'expected_amount_thb' => 'decimal:2',
        'slip_qr_data' => 'array',
        'slip_qr_amount_thb' => 'decimal:2',
        'slip_qr_paid_at' => 'datetime',
        'slip_archived_at' => 'datetime',
        'slip_deleted_at' => 'datetime',
        'slip_review_flags' => 'array',
        'slip_reviewed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(TicketOrder::class, 'ticket_order_id');
    }
}
