<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RowAnomaly extends Model
{
    use HasFactory;

    protected $fillable = [
        'validation_batch_id',
        'batch_row_id',
        'column_name',
        'column_index',
        'invalid_value',
        'error_message',
        'needs_confirmation',
        'confirmation_note',
        'is_resolved',
        'corrected_value',
    ];

    protected $casts = [
        'needs_confirmation' => 'boolean',
        'is_resolved' => 'boolean',
        'column_index' => 'integer',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ValidationBatch::class, 'validation_batch_id');
    }

    public function row(): BelongsTo
    {
        return $this->belongsTo(BatchRow::class, 'batch_row_id');
    }
}
