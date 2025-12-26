<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Quiz>
 */
class QuizFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $hasTimeLimit = fake()->boolean(70);

        return [
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'question_count' => fake()->numberBetween(10, 50),
            'time_limit_minutes' => $hasTimeLimit ? fake()->numberBetween(30, 120) : null,
            'shuffle_questions' => fake()->boolean(),
            'shuffle_answers' => fake()->boolean(),
            'allow_multiple_attempts' => fake()->boolean(60),
        ];
    }

    public function timed(): static
    {
        return $this->state(fn (array $attributes) => [
            'time_limit_minutes' => fake()->numberBetween(30, 120),
        ]);
    }

    public function untimed(): static
    {
        return $this->state(fn (array $attributes) => [
            'time_limit_minutes' => null,
        ]);
    }

    public function withoutRetakes(): static
    {
        return $this->state(fn (array $attributes) => [
            'allow_multiple_attempts' => false,
        ]);
    }

    public function withRetakes(): static
    {
        return $this->state(fn (array $attributes) => [
            'allow_multiple_attempts' => true,
        ]);
    }
}
