<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseEntryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_entry_id',
        'product_variant_id',
        'sku',
        'quantity',
        'purchase_price',
        'discount_amount',
        'gst_rate',
        'cgst_rate',
        'sgst_rate',
        'gst_amount',
        'cgst_amount',
        'sgst_amount',
        'line_total',
        'batch_number',
        'expiry_date',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'purchase_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'gst_rate' => 'decimal:2',
        'cgst_rate' => 'decimal:2',
        'sgst_rate' => 'decimal:2',
        'gst_amount' => 'decimal:2',
        'cgst_amount' => 'decimal:2',
        'sgst_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
        'expiry_date' => 'date',
    ];

    public function purchaseEntry()
    {
        return $this->belongsTo(PurchaseEntry::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
