<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RazorpayWebhookEvent extends Model
{
    public const STATUS_RECEIVED = 'received';

    public const STATUS_PROCESSED = 'processed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'event_id',
        'event_type',
        'order_id',
        'payment_id',
        'gateway_order_id',
        'gateway_payment_id',
        'payload_hash',
        'status',
        'processed_at',
        'failure_reason',
        'payload',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
        'payload' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
