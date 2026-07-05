<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    use HasFactory;

    public const TYPES = ['initiated', 'proof_submitted', 'verified', 'failed', 'refunded', 'webhook_received'];

    protected $fillable = [
        'payment_id',
        'transaction_type',
        'status',
        'amount',
        'gateway_reference',
        'payload',
        'note',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payload' => 'array',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
