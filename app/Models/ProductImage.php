<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductImage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'product_variant_id',
        'path',
        'alt_text',
        'title',
        'display_order',
        'is_primary',
        'status',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'status' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function scopeSearch($query, ?string $search)
    {
        return $query->when($search, function ($query) use ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('path', 'like', '%'.$search.'%')
                    ->orWhere('alt_text', 'like', '%'.$search.'%')
                    ->orWhere('title', 'like', '%'.$search.'%')
                    ->orWhereHas('product', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
                    ->orWhereHas('variant', fn ($query) => $query->where('variant_name', 'like', '%'.$search.'%'));
            });
        });
    }
}
