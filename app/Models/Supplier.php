<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
    ];

    protected $fillable = [
        'name',
        'contact_person',
        'mobile',
        'email',
        'gstin',
        'address',
        'city',
        'state',
        'pincode',
        'opening_balance',
        'status',
        'notes',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
    ];

    public function purchaseEntries()
    {
        return $this->hasMany(PurchaseEntry::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeSearch($query, ?string $search)
    {
        return $query->when($search, function ($query) use ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('contact_person', 'like', '%'.$search.'%')
                    ->orWhere('mobile', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('gstin', 'like', '%'.$search.'%')
                    ->orWhere('city', 'like', '%'.$search.'%');
            });
        });
    }
}
