<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ValidationProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($profile) {
            if (empty($profile->slug)) {
                $profile->slug = Str::slug($profile->name);
            }
        });
    }

    public function rules(): HasMany
    {
        return $this->hasMany(ValidationRule::class)->orderBy('column_index');
    }

    public function activeRules(): HasMany
    {
        return $this->hasMany(ValidationRule::class)
            ->where('is_active', true)
            ->orderBy('column_index');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
