<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashbackRule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'cashback_percent',
        'monthly_order_threshold',
        'eligible_category_threshold_percent',
        'redemption_multiple',
        'processing_delay_days',
        'status',
        'is_default',
        'metadata',
    ];

    protected $casts = [
        'cashback_percent' => 'decimal:2',
        'monthly_order_threshold' => 'decimal:2',
        'eligible_category_threshold_percent' => 'decimal:2',
        'redemption_multiple' => 'decimal:2',
        'processing_delay_days' => 'integer',
        'status' => 'boolean',
        'is_default' => 'boolean',
        'metadata' => 'array',
    ];
}
