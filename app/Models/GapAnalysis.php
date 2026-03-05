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
        'gap_resolutions',
        'overall_match_score',
        'previous_match_score',
        'ai_summary',
    ];

    protected function casts(): array
    {
        return [
            'strengths' => 'array',
            'gaps' => 'array',
            'gap_resolutions' => 'array',
            'overall_match_score' => 'integer',
            'previous_match_score' => 'integer',
        ];
    }

    public function getResolutionFor(string $gapArea): ?array
    {
        return ($this->gap_resolutions ?? [])[$gapArea] ?? null;
    }

    public function setResolutionFor(string $gapArea, array $resolution): void
    {
        $resolutions = $this->gap_resolutions ?? [];
        $resolutions[$gapArea] = $resolution;
        $this->update(['gap_resolutions' => $resolutions]);
    }

    public function resolvedGapCount(): int
    {
        return collect($this->gap_resolutions ?? [])->filter(
            fn (array $r) => in_array($r['status'] ?? null, ['resolved', 'acknowledged'])
        )->count();
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
