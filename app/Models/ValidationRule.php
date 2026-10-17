<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValidationRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'validation_profile_id',
        'column_name',
        'column_index',
        'data_type',
        'is_required',
        'validation_type',
        'parameters',
        'error_message',
        'needs_confirmation',
        'confirmation_note',
        'is_active',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'needs_confirmation' => 'boolean',
        'parameters' => 'array',
        'column_index' => 'integer',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(ValidationProfile::class, 'validation_profile_id');
    }
}
