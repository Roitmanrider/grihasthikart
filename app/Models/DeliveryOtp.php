<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryOtp extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'delivery_attempt_id',
        'otp_hash',
        'otp_ciphertext',
        'generated_at',
        'expires_at',
        'used_at',
        'invalidated_at',
        'failed_attempt_count',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'invalidated_at' => 'datetime',
    ];

    public function attempt()
    {
        return $this->belongsTo(DeliveryAttempt::class, 'delivery_attempt_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
