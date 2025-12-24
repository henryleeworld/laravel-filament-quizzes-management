<?php

namespace App\Livewire\Quizzes;

use App\Models\Attempt;
use App\Services\QuizService;
use Illuminate\Support\Collection;
use Livewire\Component;

class QuizResults extends Component
{
    public Attempt $attempt;

    public Collection $questions;

    protected QuizService $quizService;

    public function boot(QuizService $quizService): void
    {
        $this->quizService = $quizService;
    }

    public function mount(Attempt $attempt): void
    {
        if ($attempt->user_id !== auth()->id()) {
            abort(403);
        }

        $this->attempt = $attempt->load([
            'quiz',
            'answers.question.options',
            'answers.selectedOption',
        ]);

        $this->questions = $this->attempt->quiz->questions()
            ->with('options')
            ->get();
    }

    public function canRetakeQuiz(): bool
    {
        return $this->quizService->canUserAttemptQuiz(auth()->user(), $this->attempt->quiz);
    }

    public function retakeQuiz(): void
    {
        $this->redirect(route('quizzes.take', $this->attempt->quiz), navigate: true);
    }

    public function render()
    {
        return view('livewire.quizzes.quiz-results')
            ->layout('components.layouts.app', ['title' => __('Quiz results')]);
    }
}
