<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockLocation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'type',
        'address',
        'city',
        'state',
        'pincode',
        'manager_name',
        'phone',
        'email',
        'is_default',
        'status',
        'accepts_online_orders',
        'display_order',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'status' => 'boolean',
        'accepts_online_orders' => 'boolean',
        'display_order' => 'integer',
    ];

    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }

    public function customers()
    {
        return $this->hasMany(Customer::class, 'assigned_store_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'assigned_store_id');
    }

    public function storeVariantPrices()
    {
        return $this->hasMany(StoreVariantPrice::class);
    }

    public function carts()
    {
        return $this->hasMany(Cart::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
