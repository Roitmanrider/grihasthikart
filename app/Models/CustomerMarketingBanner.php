<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerMarketingBanner extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'subtitle',
        'image_path',
        'mobile_image_path',
        'cta_text',
        'cta_url',
        'display_order',
        'priority',
        'starts_at',
        'ends_at',
        'enabled',
        'inactive_since',
        'created_by',
        'cleanup_eligible_at',
    ];

    protected $casts = [
        'display_order' => 'integer',
        'priority' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'enabled' => 'boolean',
        'inactive_since' => 'datetime',
        'cleanup_eligible_at' => 'datetime',
    ];

    public function stores()
    {
        return $this->belongsToMany(StockLocation::class, 'customer_marketing_banner_stock_location');
    }

    public function scopeCurrent($query)
    {
        $now = now(config('app.timezone'));

        return $query
            ->where('enabled', true)
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', $now));
    }
}
