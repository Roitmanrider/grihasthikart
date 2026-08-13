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
        'anchor_cart_item_id',
        'reference',
        'status',
        'started_at',
        'last_activity_at',
        'expires_at',
        'anchor_changed_at',
        'anchor_change_count',
        'reminder_sent_at',
        'whatsapp_reminder_due_at',
        'whatsapp_reminder_attempted_at',
        'whatsapp_reminder_status',
        'whatsapp_provider_message_id',
        'whatsapp_failure_code',
        'whatsapp_failure_message',
        'follow_up_status',
        'follow_up_eligible_at',
        'follow_up_updated_at',
        'assigned_admin_user_id',
        'scarce_stock_hold',
        'risk_level',
        'cart_value_snapshot',
        'item_count_snapshot',
        'reserved_sku_count_snapshot',
        'monthly_risk_generated_at',
        'detail_cleanup_eligible_at',
        'converted_order_id',
        'closed_at',
        'close_reason',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'expires_at' => 'datetime',
        'anchor_changed_at' => 'datetime',
        'anchor_change_count' => 'integer',
        'reminder_sent_at' => 'datetime',
        'whatsapp_reminder_due_at' => 'datetime',
        'whatsapp_reminder_attempted_at' => 'datetime',
        'follow_up_eligible_at' => 'datetime',
        'follow_up_updated_at' => 'datetime',
        'scarce_stock_hold' => 'boolean',
        'cart_value_snapshot' => 'decimal:2',
        'item_count_snapshot' => 'integer',
        'reserved_sku_count_snapshot' => 'integer',
        'monthly_risk_generated_at' => 'datetime',
        'detail_cleanup_eligible_at' => 'datetime',
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

    public function anchorCartItem()
    {
        return $this->belongsTo(CartItem::class, 'anchor_cart_item_id');
    }

    public function convertedOrder()
    {
        return $this->belongsTo(Order::class, 'converted_order_id');
    }

    public function assignedAdmin()
    {
        return $this->belongsTo(User::class, 'assigned_admin_user_id');
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
