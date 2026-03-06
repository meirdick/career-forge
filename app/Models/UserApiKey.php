<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider',
        'encrypted_key',
        'is_active',
        'validated_at',
    ];

    protected function casts(): array
    {
        return [
            'encrypted_key' => 'encrypted',
            'is_active' => 'boolean',
            'validated_at' => 'datetime',
        ];
    }

    protected $hidden = [
        'encrypted_key',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
