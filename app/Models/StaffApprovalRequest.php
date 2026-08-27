<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffApprovalRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_location_id',
        'approval_type',
        'subject_type',
        'subject_id',
        'requested_by',
        'requested_at',
        'status',
        'checked_by',
        'checked_at',
        'reason_code',
        'notes',
        'evidence',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'checked_at' => 'datetime',
        'evidence' => 'array',
    ];

    public function subject()
    {
        return $this->morphTo();
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function checker()
    {
        return $this->belongsTo(User::class, 'checked_by');
    }
}
