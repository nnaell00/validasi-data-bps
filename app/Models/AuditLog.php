<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'validation_batch_id',
        'batch_row_id',
        'user_id',
        'column_name',
        'old_value',
        'new_value',
        'action',
        'notes',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ValidationBatch::class, 'validation_batch_id');
    }

    public function row(): BelongsTo
    {
        return $this->belongsTo(BatchRow::class, 'batch_row_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
