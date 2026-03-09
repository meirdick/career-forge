<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;

class Accomplishment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'experience_id',
        'title',
        'description',
        'impact',
        'sort_order',
    ];

    protected $appends = ['formatted_description'];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    protected function formattedDescription(): Attribute
    {
        return Attribute::get(function () {
            $html = Str::markdown($this->description ?? '');

            return strip_tags($html, '<p><br><strong><b><em><i><ul><ol><li><a>');
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function experience(): BelongsTo
    {
        return $this->belongsTo(Experience::class);
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class);
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }
}
