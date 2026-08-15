<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomepageBanner extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'subtitle',
        'cta_text',
        'cta_url',
        'open_in_new_tab',
        'alt_text',
        'desktop_image_path',
        'mobile_image_path',
        'enabled',
        'sort_order',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'open_in_new_tab' => 'boolean',
        'enabled' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'sort_order' => 'integer',
    ];

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('enabled', true)
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }
}
