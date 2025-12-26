<?php

namespace App\Livewire\Quizzes;

use App\Models\Attempt;
use App\Models\Question;
use App\Models\Quiz;
use App\Services\QuizService;
use Exception;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class TakeQuiz extends Component
{
    public Quiz $quiz;

    public Attempt $attempt;

    public Collection $questions;

    public array $answers = [];

    public int $currentQuestionIndex = 0;

    public ?int $remainingSeconds = null;

    public int $startTimestamp;

    protected QuizService $quizService;

    public function boot(QuizService $quizService): void
    {
        $this->quizService = $quizService;
    }

    public function mount(Quiz $quiz): void
    {
        if (! $this->quizService->canUserAttemptQuiz(auth()->user(), $quiz)) {
            $this->redirect(route('quizzes.index'), navigate: true);

            return;
        }

        $this->quiz = $quiz;

        $existingAttempt = $this->quizService->getInProgressAttempt(auth()->user(), $quiz);

        if ($existingAttempt) {
            $this->attempt = $existingAttempt;
        } else {
            $this->attempt = $this->quizService->startQuiz(auth()->user(), $quiz);
        }

        $this->questions = $this->quizService->prepareQuizQuestions($quiz);

        if ($quiz->time_limit_minutes) {
            $elapsedSeconds = $this->attempt->started_at->diffInSeconds(now());
            $totalSeconds = $quiz->time_limit_minutes * 60;
            $this->remainingSeconds = max(0, $totalSeconds - $elapsedSeconds);

            if ($this->remainingSeconds === 0) {
                $this->autoSubmit();

                return;
            }
        }

        $this->startTimestamp = now()->timestamp;

        $existingAnswers = $this->attempt->answers()
            ->get()
            ->keyBy('question_id');

        foreach ($this->questions as $question) {
            if ($existingAnswers->has($question->id)) {
                $this->answers[$question->id] = $existingAnswers[$question->id]->selected_option_id;
            }
        }
    }

    #[Computed]
    public function currentQuestion(): Question
    {
        return $this->questions[$this->currentQuestionIndex];
    }

    #[Computed]
    public function currentQuestionOptions(): Collection
    {
        $options = $this->currentQuestion->options;

        return $this->quizService->shuffleQuestionOptions($this->quiz, $options);
    }

    public function updatedAnswers($value, $questionId): void
    {
        try {
            $this->quizService->saveAnswer($this->attempt, (int) $questionId, (int) $value);
            $this->nextQuestion();
        } catch (Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => __('Failed to save answer. Please try again.'),
            ]);
        }
    }

    public function nextQuestion(): void
    {
        if ($this->currentQuestionIndex < $this->questions->count() - 1) {
            $this->currentQuestionIndex++;
        }
    }

    public function previousQuestion(): void
    {
        if ($this->currentQuestionIndex > 0) {
            $this->currentQuestionIndex--;
        }
    }

    public function goToQuestion(int $index): void
    {
        if ($index >= 0 && $index < $this->questions->count()) {
            $this->currentQuestionIndex = $index;
        }
    }

    #[On('timer-expired')]
    public function autoSubmit(): void
    {
        try {
            $this->quizService->submitQuiz($this->attempt);

            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => __('Time expired! Your quiz has been automatically submitted.'),
            ]);

            $this->redirect(route('attempts.results', $this->attempt), navigate: true);
        } catch (Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => __($e->getMessage()),
            ]);

            $this->redirect(route('quizzes.index'), navigate: true);
        }
    }

    public function submitQuiz(): void
    {
        try {
            $this->quizService->submitQuiz($this->attempt);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => __('Quiz submitted successfully!'),
            ]);

            $this->redirect(route('attempts.results', $this->attempt), navigate: true);
        } catch (Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => __($e->getMessage()),
            ]);
        }
    }

    public function render()
    {
        return view('livewire.quizzes.take-quiz')
            ->layout('components.layouts.app', ['title' => $this->quiz->title]);
    }
}
