<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ResumeSectionVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'resume_section_id',
        'label',
        'content',
        'emphasis',
        'is_ai_generated',
        'is_user_edited',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_ai_generated' => 'boolean',
            'is_user_edited' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected $appends = ['formatted_content'];

    protected function formattedContent(): Attribute
    {
        return Attribute::get(function () {
            $html = Str::markdown($this->content ?? '');

            return strip_tags($html, '<p><br><strong><b><em><i><ul><ol><li><h1><h2><h3><h4><h5><h6><a><code><pre><blockquote><span><table><thead><tbody><tr><th><td>');
        });
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(ResumeSection::class, 'resume_section_id');
    }
}
