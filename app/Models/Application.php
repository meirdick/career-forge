<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'job_posting_id',
        'resume_id',
        'status',
        'applied_at',
        'company',
        'role',
        'notes',
        'cover_letter',
        'submission_email',
    ];

    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'applied_at' => 'datetime',
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

    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class);
    }

    public function applicationNotes(): HasMany
    {
        return $this->hasMany(ApplicationNote::class);
    }

    public function statusChanges(): HasMany
    {
        return $this->hasMany(ApplicationStatusChange::class);
    }

    public function transparencyPage(): HasOne
    {
        return $this->hasOne(TransparencyPage::class);
    }
}
