<?php

namespace Database\Factories;

use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attempt>
 */
class AttemptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startedAt = fake()->dateTimeBetween('-30 days', 'now');
        $submittedAt = fake()->dateTimeBetween($startedAt, 'now');
        $timeTakenSeconds = $submittedAt->getTimestamp() - $startedAt->getTimestamp();
        $score = fake()->randomFloat(2, 50, 100);
        $totalQuestions = 20;
        $correctCount = (int) round(($score / 100) * $totalQuestions);
        $wrongCount = $totalQuestions - $correctCount;

        return [
            'user_id' => User::factory(),
            'quiz_id' => Quiz::factory(),
            'started_at' => $startedAt,
            'submitted_at' => $submittedAt,
            'score' => $score,
            'correct_count' => $correctCount,
            'wrong_count' => $wrongCount,
            'time_taken_seconds' => $timeTakenSeconds,
        ];
    }

    public function completed(): static
    {
        $startedAt = fake()->dateTimeBetween('-30 days', 'now');
        $submittedAt = fake()->dateTimeBetween($startedAt, 'now');
        $timeTakenSeconds = $submittedAt->getTimestamp() - $startedAt->getTimestamp();
        $score = fake()->randomFloat(2, 50, 100);
        $totalQuestions = 20;
        $correctCount = (int) round(($score / 100) * $totalQuestions);
        $wrongCount = $totalQuestions - $correctCount;

        return $this->state(fn (array $attributes) => [
            'started_at' => $startedAt,
            'submitted_at' => $submittedAt,
            'score' => $score,
            'correct_count' => $correctCount,
            'wrong_count' => $wrongCount,
            'time_taken_seconds' => $timeTakenSeconds,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'submitted_at' => null,
            'score' => null,
            'correct_count' => null,
            'wrong_count' => null,
            'time_taken_seconds' => null,
        ]);
    }
}
