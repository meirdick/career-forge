<?php

namespace App\Models;

use App\Enums\ResumeTemplate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Resume extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'gap_analysis_id',
        'job_posting_id',
        'title',
        'section_order',
        'is_finalized',
        'template',
        'exported_path',
        'exported_format',
        'header_config',
        'generation_status',
        'generation_progress',
        'transparency_text',
        'show_transparency',
    ];

    protected $appends = ['is_generating'];

    protected function casts(): array
    {
        return [
            'section_order' => 'array',
            'is_finalized' => 'boolean',
            'template' => ResumeTemplate::class,
            'header_config' => 'array',
            'generation_progress' => 'array',
            'show_transparency' => 'boolean',
        ];
    }

    protected function isGenerating(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::get(
            fn () => in_array($this->generation_status, ['pending', 'generating'])
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function gapAnalysis(): BelongsTo
    {
        return $this->belongsTo(GapAnalysis::class);
    }

    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(ResumeSection::class)->orderBy('sort_order');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }
}
