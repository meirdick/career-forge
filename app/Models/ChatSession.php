<?php

namespace App\Models;

use App\Enums\ChatSessionMode;
use App\Enums\ChatSessionStatus;
use App\Enums\PipelineStep;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'conversation_id',
        'job_posting_id',
        'title',
        'status',
        'mode',
        'step',
        'pipeline_key',
    ];

    protected function casts(): array
    {
        return [
            'status' => ChatSessionStatus::class,
            'mode' => ChatSessionMode::class,
            'step' => PipelineStep::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }
}
