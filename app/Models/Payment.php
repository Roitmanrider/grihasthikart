<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    public const METHODS = ['cod', 'qr', 'razorpay'];

    public const STATUSES = ['pending', 'awaiting_verification', 'paid', 'failed', 'cancelled', 'refunded'];

    protected $fillable = [
        'order_id',
        'payment_number',
        'payment_method',
        'payment_status',
        'amount',
        'currency',
        'gateway',
        'gateway_order_id',
        'gateway_payment_id',
        'gateway_signature',
        'qr_reference',
        'proof_path',
        'verified_at',
        'verified_by',
        'failure_reason',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'verified_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function transactions()
    {
        return $this->hasMany(PaymentTransaction::class)->latest();
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
