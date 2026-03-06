<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfessionalIdentity extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'values',
        'philosophy',
        'passions',
        'leadership_style',
        'collaboration_approach',
        'communication_style',
        'cultural_preferences',
        'resume_header_config',
    ];

    protected function casts(): array
    {
        return [
            'resume_header_config' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
