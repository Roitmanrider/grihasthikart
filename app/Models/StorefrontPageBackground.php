<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StorefrontPageBackground extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_location_id',
        'page_key',
        'background_path',
        'is_enabled',
        'opacity',
        'repeat_mode',
        'position',
        'size_mode',
        'enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'enabled' => 'boolean',
        'opacity' => 'decimal:2',
    ];

    public function stockLocation()
    {
        return $this->belongsTo(StockLocation::class);
    }
}
