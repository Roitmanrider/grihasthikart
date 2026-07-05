<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashbackLedger extends Model
{
    use HasFactory;

    public const TYPES = ['earned', 'redeemed', 'reversed', 'adjustment_credit', 'adjustment_debit'];

    protected $fillable = [
        'customer_id',
        'order_id',
        'coupon_id',
        'redemption_request_id',
        'ledger_type',
        'amount',
        'balance_after',
        'description',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
