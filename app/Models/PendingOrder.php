<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PendingOrder extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_CONVERTED = 'CONVERTED';

    public const STATUS_NOT_ORDERED = 'NOT_ORDERED';

    public const CLOSE_ANCHOR_REMOVED = 'ANCHOR_ITEM_REMOVED';

    public const CLOSE_CART_CLEARED = 'CART_CLEARED';

    public const CLOSE_EXPIRED = 'EXPIRED';

    protected $fillable = [
        'customer_id',
        'cart_id',
        'reference',
        'status',
        'started_at',
        'expires_at',
        'reminder_sent_at',
        'converted_order_id',
        'closed_at',
        'close_reason',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function convertedOrder()
    {
        return $this->belongsTo(Order::class, 'converted_order_id');
    }

    public function items()
    {
        return $this->hasMany(PendingOrderItem::class);
    }

    public function activeItems()
    {
        return $this->hasMany(PendingOrderItem::class)->whereNull('removed_at');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}
