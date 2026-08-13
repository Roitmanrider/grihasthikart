<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CartItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'cart_id',
        'product_variant_id',
        'sale_type',
        'daily_offer_id',
        'daily_offer_reserved_until',
        'quantity',
        'unit_price',
        'mrp',
        'product_name_snapshot',
        'variant_name_snapshot',
        'sku_snapshot',
        'hsn_code_snapshot',
        'gst_rate_snapshot',
        'attributes_snapshot',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'mrp' => 'decimal:2',
        'daily_offer_reserved_until' => 'datetime',
        'gst_rate_snapshot' => 'decimal:2',
        'attributes_snapshot' => 'array',
    ];

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function dailyOffer()
    {
        return $this->belongsTo(DailyOffer::class);
    }

    public function pendingOrderItems()
    {
        return $this->hasMany(PendingOrderItem::class);
    }

    public function getLineTotalAttribute(): float
    {
        return (float) $this->quantity * (float) $this->unit_price;
    }

    public function getLineSavingsAttribute(): float
    {
        return max(0, ((float) $this->mrp - (float) $this->unit_price) * (float) $this->quantity);
    }
}
