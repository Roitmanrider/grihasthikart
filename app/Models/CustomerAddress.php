<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerAddress extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'label',
        'recipient_name',
        'mobile',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'pincode',
        'landmark',
        'latitude',
        'longitude',
        'geofence_radius_meters',
        'is_default',
        'is_approved',
        'approval_status',
        'rejection_reason',
        'approval_status_changed_at',
        'status',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_approved' => 'boolean',
        'approval_status_changed_at' => 'datetime',
        'status' => 'boolean',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'geofence_radius_meters' => 'integer',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
