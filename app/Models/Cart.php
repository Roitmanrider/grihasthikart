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
        'coupon_id',
        'coupon_code',
        'coupon_discount_amount',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'coupon_discount_amount' => 'decimal:2',
        'expires_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
