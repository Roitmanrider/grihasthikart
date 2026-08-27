<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderStaffAssignment extends Model
{
    use HasFactory;

    public const TASK_PICKING = 'PICKING';

    public const TASK_PACKING = 'PACKING';

    public const TASK_DELIVERY = 'DELIVERY';

    protected $fillable = [
        'order_id',
        'stock_location_id',
        'task_type',
        'assigned_user_id',
        'assigned_by',
        'assigned_at',
        'started_by',
        'started_at',
        'completed_by',
        'completed_at',
        'status',
        'reassigned_from_user_id',
        'reassigned_by',
        'reassigned_at',
        'notes',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'reassigned_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function store()
    {
        return $this->belongsTo(StockLocation::class, 'stock_location_id');
    }
}
