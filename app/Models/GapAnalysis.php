<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GapAnalysis extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ideal_candidate_profile_id',
        'strengths',
        'gaps',
        'overall_match_score',
        'ai_summary',
        'is_finalized',
    ];

    protected function casts(): array
    {
        return [
            'strengths' => 'array',
            'gaps' => 'array',
            'overall_match_score' => 'integer',
            'is_finalized' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function idealCandidateProfile(): BelongsTo
    {
        return $this->belongsTo(IdealCandidateProfile::class);
    }

    public function resumes(): HasMany
    {
        return $this->hasMany(Resume::class);
    }
}
