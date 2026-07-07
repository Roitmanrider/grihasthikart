<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DailyOffer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_variant_id',
        'title',
        'offer_price',
        'starts_at',
        'ends_at',
        'is_active',
        'display_order',
        'max_quantity_per_order',
        'badge_text',
    ];

    protected $casts = [
        'offer_price' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
        'display_order' => 'integer',
        'max_quantity_per_order' => 'integer',
    ];

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function product()
    {
        return $this->hasOneThrough(
            Product::class,
            ProductVariant::class,
            'id',
            'id',
            'product_variant_id',
            'product_id'
        );
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCurrent($query)
    {
        $now = now(config('app.timezone'));

        return $query
            ->active()
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', $now));
    }

    public function getDisplayTitleAttribute(): string
    {
        return $this->title ?: (string) $this->productVariant?->product?->name;
    }

    public function discountBadge(): ?string
    {
        if ($this->badge_text) {
            return $this->badge_text;
        }

        $mrp = (float) ($this->productVariant?->mrp ?? 0);
        $offerPrice = (float) $this->offer_price;

        if ($mrp <= 0 || $offerPrice <= 0 || $offerPrice >= $mrp) {
            return null;
        }

        return round((($mrp - $offerPrice) / $mrp) * 100).'% OFF';
    }

    public function lifecycleState(): string
    {
        $now = now(config('app.timezone'));

        if (! $this->is_active || $this->trashed()) {
            return 'Inactive';
        }

        if ($this->starts_at && $this->starts_at->greaterThan($now)) {
            return 'Scheduled';
        }

        if ($this->ends_at && $this->ends_at->lessThan($now)) {
            return 'Expired';
        }

        return 'Live Now';
    }

    public function lifecycleBadgeClass(): string
    {
        return match ($this->lifecycleState()) {
            'Live Now' => 'text-bg-success',
            'Scheduled' => 'text-bg-warning',
            'Expired' => 'text-bg-danger',
            default => 'text-bg-secondary',
        };
    }
}
