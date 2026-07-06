<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeliverySlot extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'display_label',
        'status',
        'display_order',
    ];

    protected $casts = [
        'status' => 'boolean',
        'display_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function getLabelAttribute(): string
    {
        if ($this->start_time && $this->end_time) {
            return $this->formattedTime($this->start_time).' - '.$this->formattedTime($this->end_time);
        }

        return $this->display_label ?: $this->name;
    }

    private function formattedTime(string $time): string
    {
        return Carbon::createFromFormat('H:i:s', strlen($time) === 5 ? $time.':00' : $time)
            ->format('g A');
    }
}
