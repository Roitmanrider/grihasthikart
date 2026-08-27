<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'recipient_user_id',
        'stock_location_id',
        'workstream',
        'event_type',
        'related_type',
        'related_id',
        'title',
        'message',
        'action_url',
        'read_at',
        'data',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'data' => 'array',
    ];

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function stockLocation()
    {
        return $this->belongsTo(StockLocation::class);
    }

    public function related()
    {
        return $this->morphTo();
    }

    public function markAsRead(): void
    {
        if (! $this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }
}
