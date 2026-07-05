<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'sku',
        'barcode',
        'variant_name',
        'attribute_signature',
        'weight',
        'unit',
        'mrp',
        'selling_price',
        'purchase_price',
        'is_default',
        'status',
        'display_order',
    ];

    protected $casts = [
        'weight' => 'decimal:3',
        'mrp' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'is_default' => 'boolean',
        'status' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function attributeValues()
    {
        return $this->belongsToMany(AttributeValue::class, 'product_variant_attribute_value')
            ->withPivot('attribute_id')
            ->withTimestamps();
    }

    public function attributes()
    {
        return $this->belongsToMany(Attribute::class, 'product_variant_attribute_value')
            ->withPivot('attribute_value_id')
            ->withTimestamps();
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)
            ->orderByDesc('is_primary')
            ->orderBy('display_order');
    }

    public function primaryImage()
    {
        return $this->hasOne(ProductImage::class)
            ->where('status', true)
            ->where('is_primary', true);
    }

    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function scopeSearch($query, ?string $search)
    {
        return $query->when($search, function ($query) use ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('variant_name', 'like', '%'.$search.'%')
                    ->orWhere('sku', 'like', '%'.$search.'%')
                    ->orWhere('barcode', 'like', '%'.$search.'%')
                    ->orWhereHas('product', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
                    ->orWhereHas('attributeValues', fn ($query) => $query->where('value', 'like', '%'.$search.'%'));
            });
        });
    }
}
