<?php

namespace App\Services;

use App\Models\Attempt;
use App\Models\AttemptAnswer;
use App\Models\Quiz;
use App\Models\User;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class QuizService
{
    public function canUserAttemptQuiz(User $user, Quiz $quiz): bool
    {
        if ($quiz->allow_multiple_attempts) {
            return true;
        }

        return ! $quiz->attempts()
            ->where('user_id', $user->id)
            ->whereNotNull('submitted_at')
            ->exists();
    }

    public function startQuiz(User $user, Quiz $quiz): Attempt
    {
        if (! $this->canUserAttemptQuiz($user, $quiz)) {
            throw new Exception('User is not allowed to attempt this quiz.');
        }

        return Attempt::create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'started_at' => now(),
        ]);
    }

    public function getInProgressAttempt(User $user, Quiz $quiz): ?Attempt
    {
        return Attempt::query()
            ->where('user_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->whereNull('submitted_at')
            ->first();
    }

    public function prepareQuizQuestions(Quiz $quiz): Collection
    {
        $questions = $quiz->questions()
            ->with('options')
            ->get();

        if ($quiz->shuffle_questions) {
            return $questions->shuffle();
        }

        return $questions;
    }

    public function shuffleQuestionOptions(Quiz $quiz, Collection $options): Collection
    {
        $optionsWithoutCorrect = $options->map(function ($option) {
            $optionArray = $option->toArray();
            unset($optionArray['is_correct']);

            return (object) $optionArray;
        });

        if ($quiz->shuffle_answers) {
            return $optionsWithoutCorrect->shuffle()->values();
        }

        return $optionsWithoutCorrect->values();
    }

    public function saveAnswer(Attempt $attempt, int $questionId, int $optionId): void
    {
        $quiz = $attempt->quiz()->with('questions')->first();

        if (! $quiz->questions->contains('id', $questionId)) {
            throw new Exception('Question does not belong to this quiz.');
        }

        $question = $quiz->questions->find($questionId);
        if (! $question->options->contains('id', $optionId)) {
            throw new Exception('Option does not belong to this question.');
        }

        AttemptAnswer::updateOrCreate(
            [
                'attempt_id' => $attempt->id,
                'question_id' => $questionId,
            ],
            [
                'selected_option_id' => $optionId,
                'is_correct' => null,
            ]
        );
    }

    public function submitQuiz(Attempt $attempt): void
    {
        DB::transaction(function () use ($attempt) {
            $quiz = $attempt->quiz;
            $submittedAt = now();
            $timeTaken = $attempt->started_at->diffInSeconds($submittedAt);

            if ($quiz->time_limit && $timeTaken > ($quiz->time_limit * 60)) {
                throw new Exception('Time limit exceeded.');
            }

            $scoreData = $this->calculateScore($attempt);

            $attempt->update([
                'submitted_at' => $submittedAt,
                'time_taken' => $timeTaken,
                'score' => $scoreData['score'],
                'correct_count' => $scoreData['correct_count'],
                'wrong_count' => $scoreData['wrong_count'],
            ]);
        });
    }

    public function calculateScore(Attempt $attempt): array
    {
        $answers = $attempt->answers()
            ->with(['question.options', 'selectedOption'])
            ->get();

        $correctCount = 0;
        $wrongCount = 0;

        foreach ($answers as $answer) {
            $correctOption = $answer->question->options->firstWhere('is_correct', true);

            $isCorrect = $correctOption && $answer->selected_option_id === $correctOption->id;

            $answer->update(['is_correct' => $isCorrect]);

            if ($isCorrect) {
                $correctCount++;
            } else {
                $wrongCount++;
            }
        }

        $totalQuestions = $answers->count();
        $score = $totalQuestions > 0 ? ($correctCount / $totalQuestions) * 100 : 0;

        return [
            'score' => round($score, 2),
            'correct_count' => $correctCount,
            'wrong_count' => $wrongCount,
        ];
    }
}
