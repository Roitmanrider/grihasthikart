<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'brand_id',
        'name',
        'slug',
        'short_description',
        'description',
        'barcode',
        'hsn_code',
        'gst_rate',
        'manufacturer',
        'country_of_origin',
        'shelf_life',
        'minimum_order_quantity',
        'maximum_order_quantity',
        'returnable',
        'cod_available',
        'is_featured',
        'is_trending',
        'is_popular',
        'is_new_arrival',
        'status',
        'display_order',
        'default_variant_id',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    protected $casts = [
        'gst_rate' => 'decimal:2',
        'minimum_order_quantity' => 'integer',
        'maximum_order_quantity' => 'integer',
        'returnable' => 'boolean',
        'cod_available' => 'boolean',
        'is_featured' => 'boolean',
        'is_trending' => 'boolean',
        'is_popular' => 'boolean',
        'is_new_arrival' => 'boolean',
        'status' => 'boolean',
    ];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class)
            ->withPivot(['is_primary', 'display_order'])
            ->withTimestamps();
    }

    public function primaryCategory()
    {
        return $this->belongsToMany(Category::class)
            ->withPivot(['is_primary', 'display_order'])
            ->wherePivot('is_primary', true)
            ->withTimestamps();
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class)
            ->orderBy('display_order')
            ->orderBy('variant_name');
    }

    public function defaultVariant()
    {
        return $this->belongsTo(ProductVariant::class, 'default_variant_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeTrending($query)
    {
        return $query->where('is_trending', true);
    }

    public function scopePopular($query)
    {
        return $query->where('is_popular', true);
    }

    public function scopeNewArrival($query)
    {
        return $query->where('is_new_arrival', true);
    }

    public function scopeSearch($query, ?string $search)
    {
        return $query->when($search, function ($query) use ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('slug', 'like', '%'.$search.'%')
                    ->orWhere('short_description', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%')
                    ->orWhere('barcode', 'like', '%'.$search.'%')
                    ->orWhere('hsn_code', 'like', '%'.$search.'%')
                    ->orWhere('manufacturer', 'like', '%'.$search.'%')
                    ->orWhere('meta_title', 'like', '%'.$search.'%')
                    ->orWhere('meta_keywords', 'like', '%'.$search.'%')
                    ->orWhereHas('brand', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
                    ->orWhereHas('categories', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
                    ->orWhereHas('variants', function ($query) use ($search) {
                        $query->where('sku', 'like', '%'.$search.'%')
                            ->orWhere('barcode', 'like', '%'.$search.'%')
                            ->orWhere('variant_name', 'like', '%'.$search.'%');
                    });
            });
        });
    }
}
