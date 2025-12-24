<?php

namespace Database\Factories;

use App\Enums\Difficulty;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Question>
 */
class QuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'text' => fake()->sentence() . '?',
            'explanation' => fake()->paragraph(),
            'difficulty' => fake()->randomElement([Difficulty::Easy, Difficulty::Medium, Difficulty::Hard]),
            'image_path' => null,
        ];
    }

    public function easy(): static
    {
        return $this->state(fn (array $attributes) => [
            'difficulty' => Difficulty::Easy,
        ]);
    }

    public function hard(): static
    {
        return $this->state(fn (array $attributes) => [
            'difficulty' => Difficulty::Hard,
        ]);
    }

    public function medium(): static
    {
        return $this->state(fn (array $attributes) => [
            'difficulty' => Difficulty::Medium,
        ]);
    }

    public function withImage(): static
    {
        return $this->state(fn (array $attributes) => [
            'image_path' => fake()->imageUrl(640, 480, 'technology'),
        ]);
    }

    public function withoutExplanation(): static
    {
        return $this->state(fn (array $attributes) => [
            'explanation' => null,
        ]);
    }
}
