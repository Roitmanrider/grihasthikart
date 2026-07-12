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
        'grand_total',
        'notes',
        'status',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'subtotal' => 'decimal:2',
        'gst_total' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    public function items()
    {
        return $this->hasMany(PurchaseEntryItem::class);
    }

    public function movements()
    {
        return $this->morphMany(InventoryMovement::class, 'reference');
    }
}
