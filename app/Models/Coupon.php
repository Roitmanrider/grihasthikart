<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    use HasFactory, SoftDeletes;

    public const DISCOUNT_TYPES = ['fixed', 'percentage'];

    public const SOURCES = ['admin', 'cashback', 'promotion'];

    protected $fillable = [
        'code',
        'name',
        'description',
        'discount_type',
        'discount_value',
        'max_discount_amount',
        'minimum_order_amount',
        'usage_limit_total',
        'usage_limit_per_customer',
        'usage_limit_per_session',
        'customer_id',
        'starts_at',
        'expires_at',
        'status',
        'is_cashback_coupon',
        'source',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'minimum_order_amount' => 'decimal:2',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'status' => 'boolean',
        'is_cashback_coupon' => 'boolean',
        'metadata' => 'array',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function usages()
    {
        return $this->hasMany(CouponUsage::class);
    }
}
