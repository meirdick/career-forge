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
        return Attribute::get(fn () => Str::markdown($this->content ?? ''));
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(ResumeSection::class, 'resume_section_id');
    }
}
