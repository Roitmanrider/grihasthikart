<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'image',
        'banner',
        'icon',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'display_order',
        'is_featured',
        'show_in_menu',
        'show_on_homepage',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'show_in_menu' => 'boolean',
        'show_on_homepage' => 'boolean',
        'status' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')
                    ->orderBy('display_order');
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeMenu($query)
    {
        return $query->where('show_in_menu', true);
    }

    public function scopeHomepage($query)
    {
        return $query->where('show_on_homepage', true);
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }
}
