<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLink extends Model
{
    /** @use HasFactory<\Database\Factories\UserLinkFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'url',
        'label',
        'type',
        'sort_order',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get a display-friendly version of the URL.
     * If label is set, returns the label. Otherwise extracts the domain.
     */
    public function displayUrl(): string
    {
        if ($this->label) {
            return $this->label;
        }

        $host = parse_url($this->url, PHP_URL_HOST);

        if (! $host) {
            return $this->url;
        }

        return preg_replace('/^www\./', '', $host);
    }
}
