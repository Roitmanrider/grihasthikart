<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashbackRedemptionRequest extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUSES = ['pending', 'approved', 'rejected', 'coupon_generated', 'cancelled'];

    protected $fillable = [
        'customer_id',
        'requested_amount',
        'approved_amount',
        'status',
        'coupon_id',
        'customer_note',
        'admin_note',
        'requested_at',
        'approved_at',
        'rejected_at',
        'coupon_generated_at',
        'approved_by',
    ];

    protected $casts = [
        'requested_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'coupon_generated_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }
}
