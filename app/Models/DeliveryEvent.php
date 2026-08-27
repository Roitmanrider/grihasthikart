<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'delivery_attempt_id',
        'stock_location_id',
        'actor_user_id',
        'event_type',
        'occurred_at',
        'latitude',
        'longitude',
        'accuracy_meters',
        'distance_from_customer_meters',
        'geofence_result',
        'reason_code',
        'notes',
        'otp_verified',
        'otp_override_approved',
        'override_approved_by',
        'manager_review_required',
        'review_status',
        'metadata',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'otp_verified' => 'boolean',
        'otp_override_approved' => 'boolean',
        'manager_review_required' => 'boolean',
        'metadata' => 'array',
    ];
}
