<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cart extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'session_id',
        'customer_id',
        'stock_location_id',
        'coupon_id',
        'coupon_code',
        'coupon_discount_amount',
        'status',
        'expires_at',
        'revision',
    ];

    protected $casts = [
        'coupon_discount_amount' => 'decimal:2',
        'expires_at' => 'datetime',
        'revision' => 'integer',
    ];

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function stockLocation()
    {
        return $this->belongsTo(StockLocation::class);
    }

    public function pendingOrders()
    {
        return $this->hasMany(PendingOrder::class);
    }

    public function activePendingOrder()
    {
        return $this->hasOne(PendingOrder::class)->where('status', PendingOrder::STATUS_ACTIVE);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
