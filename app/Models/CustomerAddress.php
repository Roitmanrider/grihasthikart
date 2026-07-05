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
        'is_default',
        'is_approved',
        'status',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_approved' => 'boolean',
        'status' => 'boolean',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
