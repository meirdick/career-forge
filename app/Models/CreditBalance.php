<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'balance',
        'lifetime_purchased',
        'lifetime_consumed',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'integer',
            'lifetime_purchased' => 'integer',
            'lifetime_consumed' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
