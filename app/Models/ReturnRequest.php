<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnRequest extends Model
{
    use HasFactory;

    public const STATUSES = ['requested', 'approved', 'rejected', 'refunded', 'closed'];

    public const QUANTITY_HOLDING_STATUSES = ['requested', 'approved', 'refunded', 'closed'];

    protected $fillable = [
        'order_id',
        'customer_id',
        'return_number',
        'status',
        'reason',
        'customer_notes',
        'admin_notes',
        'requested_at',
        'approved_at',
        'rejected_at',
        'closed_at',
        'refund_amount',
        'restock_items',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'closed_at' => 'datetime',
        'refund_amount' => 'decimal:2',
        'restock_items' => 'boolean',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(ReturnRequestItem::class);
    }
}
