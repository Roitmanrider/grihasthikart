<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerCreditTransaction extends Model
{
    use HasFactory;

    public const RETURN_REFUND_CREDIT = 'RETURN_REFUND_CREDIT';

    public const MANUAL_CREDIT = 'MANUAL_CREDIT';

    public const MANUAL_DEBIT = 'MANUAL_DEBIT';

    public const ORDER_REDEMPTION_DEBIT = 'ORDER_REDEMPTION_DEBIT';

    public const ORDER_CANCELLATION_CREDIT = 'ORDER_CANCELLATION_CREDIT';

    public const MANUAL_ADJUSTMENT = 'MANUAL_ADJUSTMENT';

    public const CREDIT_TYPES = [
        self::RETURN_REFUND_CREDIT,
        self::MANUAL_CREDIT,
        self::ORDER_CANCELLATION_CREDIT,
    ];

    public const DEBIT_TYPES = [
        self::ORDER_REDEMPTION_DEBIT,
        self::MANUAL_DEBIT,
    ];

    protected $fillable = [
        'customer_id',
        'type',
        'amount',
        'balance_after',
        'order_id',
        'return_request_id',
        'source',
        'description',
        'created_by',
        'idempotency_key',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function returnRequest()
    {
        return $this->belongsTo(ReturnRequest::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
