<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class JobPosting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'url',
        'raw_text',
        'title',
        'company',
        'location',
        'seniority_level',
        'compensation',
        'remote_policy',
        'parsed_data',
        'analyzed_at',
    ];

    protected function casts(): array
    {
        return [
            'parsed_data' => 'array',
            'analyzed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function idealCandidateProfile(): HasOne
    {
        return $this->hasOne(IdealCandidateProfile::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function resumes(): HasMany
    {
        return $this->hasMany(Resume::class);
    }
}
