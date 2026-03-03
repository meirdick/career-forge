<?php

namespace Database\Factories;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ApplicationStatusChange>
 */
class ApplicationStatusChangeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'application_id' => Application::factory(),
            'from_status' => fake()->optional(0.8)->randomElement(ApplicationStatus::cases()),
            'to_status' => fake()->randomElement(ApplicationStatus::cases()),
            'notes' => fake()->optional(0.4)->sentence(),
        ];
    }
}
