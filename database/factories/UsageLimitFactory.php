<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UsageLimit>
 */
class UsageLimitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'job_postings_used' => 0,
            'documents_used' => 0,
        ];
    }
}
