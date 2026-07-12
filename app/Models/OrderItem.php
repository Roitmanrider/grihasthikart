<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_variant_id',
        'product_id',
        'product_name_snapshot',
        'variant_name_snapshot',
        'sku_snapshot',
        'barcode_snapshot',
        'hsn_code_snapshot',
        'gst_rate_snapshot',
        'attributes_snapshot',
        'quantity',
        'mrp',
        'unit_price',
        'line_subtotal',
        'line_mrp_total',
        'line_savings',
        'tax_amount',
        'line_total',
    ];

    protected $casts = [
        'attributes_snapshot' => 'array',
        'quantity' => 'decimal:3',
        'mrp' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'line_subtotal' => 'decimal:2',
        'line_mrp_total' => 'decimal:2',
        'line_savings' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function returnRequestItems()
    {
        return $this->hasMany(ReturnRequestItem::class);
    }
}
