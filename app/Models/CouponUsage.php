<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CouponUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'coupon_id',
        'order_id',
        'customer_id',
        'session_id',
        'code_snapshot',
        'discount_type_snapshot',
        'discount_value_snapshot',
        'discount_amount',
        'cart_subtotal_snapshot',
        'used_at',
        'metadata',
    ];

    protected $casts = [
        'discount_value_snapshot' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'cart_subtotal_snapshot' => 'decimal:2',
        'used_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
