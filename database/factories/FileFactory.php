<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\File\Models\File;
use Illuminate\Database\Eloquent\Factories\Factory;

class FileFactory extends Factory
{
    protected $model = File::class;

    public function definition(): array
    {
        return [
            'original_name' => $this->faker->word() . '.pdf',
            'stored_name' => $this->faker->uuid() . '.pdf',
            'mime_type' => 'application/pdf',
            'size' => $this->faker->numberBetween(1024, 10485760),
            'path' => 'files/' . $this->faker->uuid() . '.pdf',
            'expires_at' => now()->addHours(24),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subHour(),
        ]);
    }

    public function docx(): static
    {
        return $this->state(fn (array $attributes) => [
            'original_name' => $this->faker->word() . '.docx',
            'stored_name' => $this->faker->uuid() . '.docx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'path' => 'files/' . $this->faker->uuid() . '.docx',
        ]);
    }
}
