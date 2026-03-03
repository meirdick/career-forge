<?php

namespace App\Models;

use App\Enums\ResumeSectionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResumeSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'resume_id',
        'type',
        'title',
        'sort_order',
        'selected_variant_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => ResumeSectionType::class,
            'sort_order' => 'integer',
        ];
    }

    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ResumeSectionVariant::class)->orderBy('sort_order');
    }

    public function selectedVariant(): BelongsTo
    {
        return $this->belongsTo(ResumeSectionVariant::class, 'selected_variant_id');
    }
}
