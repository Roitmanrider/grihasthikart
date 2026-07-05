<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;

class Attribute extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const TYPES = [
        'text',
        'number',
        'select',
        'boolean',
        'color',
        'weight',
        'volume',
        'size',
        'pack',
    ];

    protected $fillable = [
        'name',
        'slug',
        'type',
        'display_order',
        'is_filterable',
        'is_variant_defining',
        'status',
    ];

    protected $casts = [
        'is_filterable' => 'boolean',
        'is_variant_defining' => 'boolean',
        'status' => 'boolean',
    ];

    public static function isValidType(string $type): bool
    {
        return in_array($type, self::TYPES, true);
    }

    public function setTypeAttribute(string $type): void
    {
        if (! self::isValidType($type)) {
            throw new InvalidArgumentException('Invalid attribute type.');
        }

        $this->attributes['type'] = $type;
    }

    public function values()
    {
        return $this->hasMany(AttributeValue::class)
            ->orderBy('display_order')
            ->orderBy('value');
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function scopeFilterable($query)
    {
        return $query->where('is_filterable', true);
    }

    public function scopeVariantDefining($query)
    {
        return $query->where('is_variant_defining', true);
    }

    public function scopeSearch($query, ?string $search)
    {
        return $query->when($search, function ($query) use ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('slug', 'like', '%'.$search.'%')
                    ->orWhere('type', 'like', '%'.$search.'%');
            });
        });
    }
}
