<?php

namespace Database\Factories;

use App\Models\Attempt;
use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AttemptAnswer>
 */
class AttemptAnswerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'attempt_id' => Attempt::factory(),
            'question_id' => Question::factory(),
            'selected_option_id' => QuestionOption::factory(),
            'is_correct' => fake()->boolean(70),
            'time_spent' => fake()->numberBetween(10, 180),
        ];
    }

    public function correct(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_correct' => true,
        ]);
    }

    public function incorrect(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_correct' => false,
        ]);
    }

    public function skipped(): static
    {
        return $this->state(fn (array $attributes) => [
            'selected_option_id' => null,
            'is_correct' => null,
            'time_spent' => fake()->numberBetween(5, 30),
        ]);
    }
}
