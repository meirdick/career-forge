<?php

namespace Database\Factories;

use App\Enums\ChatSessionMode;
use App\Enums\ChatSessionStatus;
use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ChatSession>
 */
class ChatSessionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'conversation_id' => null,
            'job_posting_id' => null,
            'title' => fake()->sentence(3),
            'status' => ChatSessionStatus::Active,
            'mode' => ChatSessionMode::General,
        ];
    }

    public function general(): static
    {
        return $this->state(fn (array $attributes) => [
            'mode' => ChatSessionMode::General,
        ]);
    }

    public function jobSpecific(): static
    {
        return $this->state(fn (array $attributes) => [
            'mode' => ChatSessionMode::JobSpecific,
            'job_posting_id' => JobPosting::factory(),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ChatSessionStatus::Archived,
        ]);
    }

    public function withConversation(): static
    {
        return $this->state(fn (array $attributes) => [
            'conversation_id' => fake()->uuid(),
        ]);
    }
}
