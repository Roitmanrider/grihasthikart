<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    use HasFactory;

    public const TYPES = [
        'opening',
        'purchase',
        'adjustment_in',
        'adjustment_out',
        'damaged',
        'return_in',
        'reservation',
        'reservation_release',
        'sale',
        'cancellation_return',
    ];

    protected $fillable = [
        'inventory_id',
        'product_variant_id',
        'stock_location_id',
        'movement_type',
        'quantity',
        'balance_after',
        'reference_type',
        'reference_id',
        'note',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'balance_after' => 'decimal:3',
    ];

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function stockLocation()
    {
        return $this->belongsTo(StockLocation::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
