<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Document>
 */
class DocumentFactory extends Factory
{
    public function definition(): array
    {
        $filename = fake()->word().'.'.fake()->randomElement(['pdf', 'docx', 'txt']);

        return [
            'user_id' => User::factory(),
            'documentable_id' => null,
            'documentable_type' => null,
            'filename' => $filename,
            'disk' => config('filesystems.default'),
            'path' => 'documents/'.$filename,
            'mime_type' => 'application/pdf',
            'size' => fake()->numberBetween(10000, 5000000),
            'metadata' => null,
        ];
    }
}
