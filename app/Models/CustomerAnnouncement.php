<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerAnnouncement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'message',
        'audience_type',
        'sticky',
        'dismissible',
        'priority',
        'cta_text',
        'cta_url',
        'starts_at',
        'ends_at',
        'enabled',
        'inactive_since',
        'created_by',
        'cleanup_eligible_at',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'enabled' => 'boolean',
        'sticky' => 'boolean',
        'dismissible' => 'boolean',
        'priority' => 'integer',
        'inactive_since' => 'datetime',
        'cleanup_eligible_at' => 'datetime',
    ];

    public function stores()
    {
        return $this->belongsToMany(StockLocation::class, 'customer_announcement_stock_location');
    }

    public function customers()
    {
        return $this->belongsToMany(Customer::class, 'customer_announcement_customer');
    }

    public function dismissals()
    {
        return $this->hasMany(CustomerAnnouncementDismissal::class);
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
