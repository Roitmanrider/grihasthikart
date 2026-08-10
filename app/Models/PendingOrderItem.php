<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PendingOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'pending_order_id',
        'cart_item_id',
        'product_id',
        'product_variant_id',
        'product_name_snapshot',
        'variant_name_snapshot',
        'sku_snapshot',
        'quantity',
        'price_snapshot',
        'sale_type',
        'daily_offer_id',
        'added_at',
        'removed_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'price_snapshot' => 'decimal:2',
        'added_at' => 'datetime',
        'removed_at' => 'datetime',
    ];

    public function pendingOrder()
    {
        return $this->belongsTo(PendingOrder::class);
    }

    public function cartItem()
    {
        return $this->belongsTo(CartItem::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function dailyOffer()
    {
        return $this->belongsTo(DailyOffer::class);
    }
}
