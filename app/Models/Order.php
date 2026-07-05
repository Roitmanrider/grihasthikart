<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUSES = ['placed', 'confirmed', 'preparing', 'ready_for_delivery', 'delivered', 'cancelled'];

    public const PAYMENT_STATUSES = ['pending', 'awaiting_verification', 'paid', 'failed', 'cancelled', 'refunded'];

    public const PAYMENT_METHODS = ['cod', 'qr', 'razorpay'];

    protected $fillable = [
        'order_number',
        'cart_id',
        'session_id',
        'customer_id',
        'customer_name',
        'customer_mobile',
        'customer_email',
        'delivery_address_line1',
        'delivery_address_line2',
        'delivery_city',
        'delivery_state',
        'delivery_pincode',
        'delivery_landmark',
        'delivery_date',
        'delivery_slot',
        'payment_method',
        'payment_status',
        'order_status',
        'subtotal',
        'total_mrp',
        'total_savings',
        'tax_total',
        'delivery_charge',
        'discount_total',
        'grand_total',
        'notes',
        'admin_notes',
        'placed_at',
        'confirmed_at',
        'cancelled_at',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'subtotal' => 'decimal:2',
        'total_mrp' => 'decimal:2',
        'total_savings' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'delivery_charge' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'placed_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistories()
    {
        return $this->hasMany(OrderStatusHistory::class)->latest();
    }

    public function payment()
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }
}
