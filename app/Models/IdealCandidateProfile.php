<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IdealCandidateProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_posting_id',
        'required_skills',
        'preferred_skills',
        'experience_profile',
        'cultural_fit',
        'language_guidance',
        'red_flags',
        'company_research',
        'industry_standards',
        'is_user_edited',
    ];

    protected function casts(): array
    {
        return [
            'required_skills' => 'array',
            'preferred_skills' => 'array',
            'experience_profile' => 'array',
            'cultural_fit' => 'array',
            'language_guidance' => 'array',
            'red_flags' => 'array',
            'company_research' => 'array',
            'industry_standards' => 'array',
            'is_user_edited' => 'boolean',
        ];
    }

    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }

    public function gapAnalyses(): HasMany
    {
        return $this->hasMany(GapAnalysis::class);
    }
}
