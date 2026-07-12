<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
    use HasFactory;

    public const TYPES = ['increase', 'decrease', 'set'];

    public const REASONS = [
        'damage',
        'wastage',
        'expiry',
        'physical_count_mismatch',
        'manual_correction',
        'return_adjustment',
        'other',
    ];

    protected $fillable = [
        'product_variant_id',
        'inventory_id',
        'adjustment_type',
        'quantity',
        'before_quantity',
        'after_quantity',
        'reason',
        'notes',
        'reference_number',
        'adjustment_date',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'before_quantity' => 'decimal:3',
        'after_quantity' => 'decimal:3',
        'adjustment_date' => 'date',
    ];

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function movements()
    {
        return $this->morphMany(InventoryMovement::class, 'reference');
    }
}
