<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerCartRiskMonthly extends Model
{
    use HasFactory;

    protected $table = 'customer_cart_risk_monthly';

    protected $fillable = [
        'customer_id',
        'period_month',
        'risk_level',
        'risk_score',
        'cart_sessions',
        'converted_count',
        'abandoned_count',
        'expired_count',
        'scarce_stock_hold_count',
        'anchor_change_count',
        'conversion_rate',
        'generated_at',
    ];

    protected $casts = [
        'period_month' => 'date',
        'risk_score' => 'integer',
        'cart_sessions' => 'integer',
        'converted_count' => 'integer',
        'abandoned_count' => 'integer',
        'expired_count' => 'integer',
        'scarce_stock_hold_count' => 'integer',
        'anchor_change_count' => 'integer',
        'conversion_rate' => 'decimal:2',
        'generated_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
