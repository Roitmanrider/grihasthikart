<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inventory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_variant_id',
        'stock_location_id',
        'quantity_on_hand',
        'reserved_quantity',
        'damaged_quantity',
        'low_stock_threshold',
        'reorder_level',
        'target_stock_level',
        'low_stock_state',
        'low_stock_notified_at',
        'status',
    ];

    protected $casts = [
        'quantity_on_hand' => 'decimal:3',
        'reserved_quantity' => 'decimal:3',
        'damaged_quantity' => 'decimal:3',
        'low_stock_threshold' => 'decimal:3',
        'reorder_level' => 'decimal:3',
        'target_stock_level' => 'decimal:3',
        'low_stock_notified_at' => 'datetime',
        'status' => 'boolean',
    ];

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function stockLocation()
    {
        return $this->belongsTo(StockLocation::class);
    }

    public function movements()
    {
        return $this->hasMany(InventoryMovement::class)->latest();
    }

    public function getAvailableQuantityAttribute(): float
    {
        return (float) $this->quantity_on_hand - (float) $this->reserved_quantity - (float) $this->damaged_quantity;
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->reorder_level !== null
            && $this->available_quantity > 0
            && $this->available_quantity <= (float) $this->reorder_level;
    }

    public function getStockStatusAttribute(): string
    {
        if ($this->available_quantity <= 0) {
            return 'OUT_OF_STOCK';
        }

        if ($this->reorder_level !== null && $this->available_quantity <= (float) $this->reorder_level) {
            return 'LOW_STOCK';
        }

        return 'IN_STOCK';
    }

    public function getRecommendedPurchaseQuantityAttribute(): ?float
    {
        if ($this->target_stock_level === null) {
            return null;
        }

        return round(max((float) $this->target_stock_level - $this->available_quantity, 0), 3);
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
