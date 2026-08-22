<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerAnnouncementDismissal extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_announcement_id',
        'customer_id',
        'dismissed_at',
    ];

    protected $casts = [
        'dismissed_at' => 'datetime',
    ];
}
