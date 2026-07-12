<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseEntry extends Model
{
    use HasFactory;

    public const STATUS_POSTED = 'posted';

    protected $fillable = [
        'supplier_id',
        'purchase_number',
        'bill_number',
        'purchase_date',
        'subtotal',
        'gst_total',
        'discount_total',
        'cgst_total',
        'sgst_total',
        'grand_total',
        'freight_allocation',
        'notes',
        'status',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'subtotal' => 'decimal:2',
        'gst_total' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'cgst_total' => 'decimal:2',
        'sgst_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'freight_allocation' => 'decimal:2',
    ];

    public function items()
    {
        return $this->hasMany(PurchaseEntryItem::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function movements()
    {
        return $this->morphMany(InventoryMovement::class, 'reference');
    }
}
