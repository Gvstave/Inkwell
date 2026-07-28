<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(6),
            'excerpt' => fake()->sentence(),
            'content' => fake()->paragraphs(4, true),
            'published' => false,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => ['published' => true]);
    }
}
