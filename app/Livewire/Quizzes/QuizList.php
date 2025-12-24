<?php

namespace App\Livewire\Quizzes;

use App\Models\Quiz;
use App\Services\QuizService;
use Illuminate\Support\Collection;
use Livewire\Component;

class QuizList extends Component
{
    public Collection $quizzes;

    public Collection $userAttempts;

    protected QuizService $quizService;

    public function boot(QuizService $quizService): void
    {
        $this->quizService = $quizService;
    }

    public function mount(): void
    {
        $this->quizzes = Quiz::query()
            ->withCount('questions')
            ->with(['attempts' => function ($query) {
                $query->where('user_id', auth()->id())
                    ->whereNotNull('submitted_at')
                    ->select('id', 'quiz_id', 'score', 'submitted_at')
                    ->latest('submitted_at');
            }])
            ->get();

        $this->userAttempts = $this->quizzes
            ->pluck('attempts')
            ->flatten()
            ->keyBy('quiz_id');
    }

    public function canAttemptQuiz(Quiz $quiz): bool
    {
        return $this->quizService->canUserAttemptQuiz(auth()->user(), $quiz);
    }

    public function startQuiz(int $quizId): void
    {
        $quiz = Quiz::findOrFail($quizId);

        if (! $this->canAttemptQuiz($quiz)) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => __('You are not allowed to attempt this quiz.'),
            ]);

            return;
        }

        $this->redirect(route('quizzes.take', $quiz));
    }

    public function render()
    {
        return view('livewire.quizzes.quiz-list')
            ->layout('components.layouts.app', ['title' => __('Quizzes')]);
    }
}
