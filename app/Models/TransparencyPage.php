<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransparencyPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'application_id',
        'slug',
        'authorship_statement',
        'research_summary',
        'ideal_profile_summary',
        'section_decisions',
        'tool_description',
        'repository_url',
        'is_published',
        'content_html',
    ];

    protected function casts(): array
    {
        return [
            'section_decisions' => 'array',
            'is_published' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function views(): HasMany
    {
        return $this->hasMany(TransparencyPageView::class);
    }
}
