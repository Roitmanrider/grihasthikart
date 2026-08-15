<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomepageSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'section_key',
        'section_type',
        'title',
        'subtitle',
        'enabled',
        'sort_order',
        'desktop_item_limit',
        'mobile_item_limit',
        'source_mode',
        'root_category_id',
        'view_all_enabled',
        'view_all_text',
        'view_all_url',
        'configuration',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'view_all_enabled' => 'boolean',
        'configuration' => 'array',
        'desktop_item_limit' => 'integer',
        'mobile_item_limit' => 'integer',
        'sort_order' => 'integer',
    ];

    public function rootCategory()
    {
        return $this->belongsTo(Category::class, 'root_category_id');
    }

    public function selectedCategories()
    {
        return $this->belongsToMany(Category::class, 'homepage_section_categories')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('homepage_section_categories.sort_order');
    }

    public function selectedProducts()
    {
        return $this->belongsToMany(Product::class, 'homepage_section_products')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('homepage_section_products.sort_order');
    }
}
