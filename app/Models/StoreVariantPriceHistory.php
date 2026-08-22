<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreVariantPriceHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_location_id',
        'product_variant_id',
        'old_mrp',
        'old_selling_price',
        'new_mrp',
        'new_selling_price',
        'change_reason',
        'changed_by',
        'changed_at',
    ];

    protected $casts = [
        'old_mrp' => 'decimal:2',
        'old_selling_price' => 'decimal:2',
        'new_mrp' => 'decimal:2',
        'new_selling_price' => 'decimal:2',
        'changed_at' => 'datetime',
    ];
}
