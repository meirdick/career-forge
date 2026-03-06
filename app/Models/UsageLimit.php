<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageLimit extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'job_postings_used',
        'documents_used',
    ];

    protected function casts(): array
    {
        return [
            'job_postings_used' => 'integer',
            'documents_used' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
