<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function section(): BelongsTo
    {
        return $this->belongsTo(ResumeSection::class, 'resume_section_id');
    }
}
