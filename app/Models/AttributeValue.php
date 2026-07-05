<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttributeValue extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'attribute_id',
        'value',
        'slug',
        'display_order',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }

    public function productVariants()
    {
        return $this->belongsToMany(ProductVariant::class, 'product_variant_attribute_value')
            ->withPivot('attribute_id')
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function scopeSearch($query, ?string $search)
    {
        return $query->when($search, function ($query) use ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('value', 'like', '%'.$search.'%')
                    ->orWhere('slug', 'like', '%'.$search.'%')
                    ->orWhereHas('attribute', function ($query) use ($search) {
                        $query->where('name', 'like', '%'.$search.'%');
                    });
            });
        });
    }
}
