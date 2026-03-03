<?php

namespace App\Models;

use App\Enums\EducationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EducationEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'institution',
        'title',
        'field',
        'url',
        'description',
        'started_at',
        'completed_at',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'type' => EducationType::class,
            'started_at' => 'date',
            'completed_at' => 'date',
            'sort_order' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
