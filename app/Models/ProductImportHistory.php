<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductImportHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'filename',
        'rows_processed',
        'products_created',
        'products_updated',
        'variants_created',
        'variants_updated',
        'rows_skipped',
        'rows_failed',
        'error_count',
        'duration_seconds',
        'successful',
        'duplicate_action',
        'error_report_path',
        'summary',
    ];

    protected $casts = [
        'rows_processed' => 'integer',
        'products_created' => 'integer',
        'products_updated' => 'integer',
        'variants_created' => 'integer',
        'variants_updated' => 'integer',
        'rows_skipped' => 'integer',
        'rows_failed' => 'integer',
        'error_count' => 'integer',
        'duration_seconds' => 'decimal:3',
        'successful' => 'boolean',
        'summary' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
