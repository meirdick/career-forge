<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransparencyPageView extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'transparency_page_id',
        'ip_address',
        'user_agent',
        'referer',
        'viewed_at',
    ];

    protected function casts(): array
    {
        return [
            'viewed_at' => 'datetime',
        ];
    }

    public function transparencyPage(): BelongsTo
    {
        return $this->belongsTo(TransparencyPage::class);
    }
}
