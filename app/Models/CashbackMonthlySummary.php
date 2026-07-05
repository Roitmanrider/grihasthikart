<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashbackMonthlySummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'year',
        'month',
        'total_delivered_order_amount',
        'eligible_category_order_amount',
        'coupon_discount_excluded_amount',
        'eligible_cashback_base',
        'cashback_percent',
        'cashback_amount',
        'eligibility_status',
        'processed_at',
        'metadata',
    ];

    protected $casts = [
        'total_delivered_order_amount' => 'decimal:2',
        'eligible_category_order_amount' => 'decimal:2',
        'coupon_discount_excluded_amount' => 'decimal:2',
        'eligible_cashback_base' => 'decimal:2',
        'cashback_percent' => 'decimal:2',
        'cashback_amount' => 'decimal:2',
        'processed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
