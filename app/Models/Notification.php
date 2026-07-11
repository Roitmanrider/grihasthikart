<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    public const AUDIENCE_ADMIN = 'admin';

    public const AUDIENCE_CUSTOMER = 'customer';

    protected $fillable = [
        'notifiable_type',
        'notifiable_id',
        'audience',
        'customer_id',
        'type',
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

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function notifiable()
    {
        return $this->morphTo();
    }

    public function scopeAdmin($query)
    {
        return $query->where('audience', self::AUDIENCE_ADMIN);
    }

    public function scopeForCustomer($query, Customer $customer)
    {
        return $query
            ->where('audience', self::AUDIENCE_CUSTOMER)
            ->where('customer_id', $customer->id);
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function markAsRead(): void
    {
        if ($this->read_at) {
            return;
        }

        $this->update(['read_at' => now()]);
    }
}
