<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AiInteraction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'interactable_id',
        'interactable_type',
        'purpose',
        'model_used',
        'prompt_summary',
        'input_tokens',
        'output_tokens',
        'duration_ms',
    ];

    protected function casts(): array
    {
        return [
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
            'duration_ms' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function interactable(): MorphTo
    {
        return $this->morphTo();
    }
}
