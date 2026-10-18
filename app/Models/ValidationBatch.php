<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ValidationBatch extends Model
{
    use HasFactory;

    public const PRESERVED_SOURCE_COLUMN_INDICES = [12, 13, 14];

    protected $fillable = [
        'user_id',
        'validation_profile_id',
        'file_name',
        'file_path',
        'total_rows',
        'status',
        'headers',
        'summary',
    ];

    protected $casts = [
        'headers' => 'array',
        'summary' => 'array',
        'total_rows' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(ValidationProfile::class, 'validation_profile_id');
    }

    public function rows(): HasMany
    {
        return $this->hasMany(BatchRow::class)->orderBy('row_number');
    }

    public function anomalies(): HasMany
    {
        return $this->hasMany(RowAnomaly::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class)->latest();
    }
}
