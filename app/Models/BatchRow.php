<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BatchRow extends Model
{
    use HasFactory;

    protected $fillable = [
        'validation_batch_id',
        'row_index',
        'row_number',
        'original_data',
        'current_data',
        'has_anomalies',
        'is_corrected',
    ];

    protected $casts = [
        'original_data' => 'array',
        'current_data' => 'array',
        'has_anomalies' => 'boolean',
        'is_corrected' => 'boolean',
        'row_index' => 'integer',
        'row_number' => 'integer',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ValidationBatch::class, 'validation_batch_id');
    }

    public function anomalies(): HasMany
    {
        return $this->hasMany(RowAnomaly::class);
    }

    public function unresolvedAnomalies(): HasMany
    {
        return $this->hasMany(RowAnomaly::class)->where('is_resolved', false);
    }
}
