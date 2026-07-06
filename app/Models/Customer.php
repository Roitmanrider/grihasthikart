<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'mobile',
        'email',
        'status',
        'is_premium',
        'cashback_enabled',
        'monthly_cashback_threshold',
        'category_cashback_threshold_percent',
        'notes',
        'last_login_at',
    ];

    protected $casts = [
        'status' => 'boolean',
        'is_premium' => 'boolean',
        'cashback_enabled' => 'boolean',
        'monthly_cashback_threshold' => 'decimal:2',
        'category_cashback_threshold_percent' => 'decimal:2',
        'last_login_at' => 'datetime',
    ];

    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function approvedAddresses()
    {
        return $this->addresses()->where('is_approved', true)->where('status', true);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function carts()
    {
        return $this->hasMany(Cart::class);
    }

    public function wishlistItems()
    {
        return $this->hasMany(WishlistItem::class);
    }

    public function cashbackLedgers()
    {
        return $this->hasMany(CashbackLedger::class)->latest();
    }

    public function cashbackRedemptionRequests()
    {
        return $this->hasMany(CashbackRedemptionRequest::class)->latest();
    }

    public function cashbackMonthlySummaries()
    {
        return $this->hasMany(CashbackMonthlySummary::class)->latest();
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
