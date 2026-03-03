<?php

namespace App\Models;

use App\Enums\SkillCategory;
use App\Enums\SkillProficiency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Scout\Searchable;

class Skill extends Model
{
    use HasFactory, Searchable;

    protected $fillable = [
        'user_id',
        'name',
        'category',
        'proficiency',
        'ai_inferred_proficiency',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'category' => SkillCategory::class,
            'proficiency' => SkillProficiency::class,
            'ai_inferred_proficiency' => SkillProficiency::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function experiences(): BelongsToMany
    {
        return $this->belongsToMany(Experience::class);
    }

    public function accomplishments(): BelongsToMany
    {
        return $this->belongsToMany(Accomplishment::class);
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class);
    }
}
