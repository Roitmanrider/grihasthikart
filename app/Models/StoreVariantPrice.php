<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreVariantPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_location_id',
        'product_variant_id',
        'mrp',
        'selling_price',
        'effective_from',
        'effective_until',
        'source',
        'changed_by',
        'status',
    ];

    protected $casts = [
        'mrp' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'effective_from' => 'datetime',
        'effective_until' => 'datetime',
        'status' => 'boolean',
    ];

    public function stockLocation()
    {
        return $this->belongsTo(StockLocation::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function scopeEffective($query)
    {
        $now = now(config('app.timezone'));

        return $query
            ->where('status', true)
            ->where(fn ($query) => $query->whereNull('effective_from')->orWhere('effective_from', '<=', $now))
            ->where(fn ($query) => $query->whereNull('effective_until')->orWhere('effective_until', '>=', $now));
    }
}
