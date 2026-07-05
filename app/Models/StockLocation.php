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
        'is_default',
        'status',
        'display_order',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'status' => 'boolean',
        'display_order' => 'integer',
    ];

    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
