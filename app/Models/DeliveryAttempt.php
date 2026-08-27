<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'order_staff_assignment_id',
        'stock_location_id',
        'delivery_agent_id',
        'attempt_number',
        'status',
        'started_at',
        'completed_at',
        'invalidated_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'invalidated_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function assignment()
    {
        return $this->belongsTo(OrderStaffAssignment::class, 'order_staff_assignment_id');
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'delivery_agent_id');
    }
}
