<?php

namespace Tests\Feature\Quizzes;

use App\Livewire\Quizzes\TakeQuiz;
use App\Models\Attempt;
use App\Models\AttemptAnswer;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class TakeQuizTest extends TestCase
{
    public function test_guests_are_redirected_to_login_page(): void
    {
        $quiz = Quiz::factory()->create();

        $this->get(route('quizzes.take', $quiz))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_quiz_taking_page(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);
        $quiz = Quiz::factory()->create(['allow_multiple_attempts' => true]);

        $questions = Question::factory()->count(3)->create();
        $quiz->questions()->attach($questions->pluck('id'));

        foreach ($questions as $question) {
            QuestionOption::factory()->count(4)->create([
                'question_id' => $question->id,
            ]);
        }

        $this->actingAs($user)
            ->get(route('quizzes.take', $quiz))
            ->assertOk();
    }

    public function test_quiz_start_creates_new_attempt(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);
        $quiz = Quiz::factory()->create(['allow_multiple_attempts' => true]);

        $questions = Question::factory()->count(3)->create();
        $quiz->questions()->attach($questions->pluck('id'));

        foreach ($questions as $question) {
            QuestionOption::factory()->count(4)->create([
                'question_id' => $question->id,
            ]);
        }

        $this->actingAs($user);

        $this->assertSame(
            0,
            Attempt::where('user_id', $user->id)
                ->where('quiz_id', $quiz->id)
                ->count()
        );

        Livewire::test(TakeQuiz::class, ['quiz' => $quiz]);

        $this->assertSame(
            1,
            Attempt::where('user_id', $user->id)
                ->where('quiz_id', $quiz->id)
                ->count()
        );
    }

    public function test_user_cannot_attempt_quiz_when_retakes_not_allowed_and_completed(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);
        $quiz = Quiz::factory()->create(['allow_multiple_attempts' => false]);

        Attempt::factory()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'started_at' => now()->subHour(),
            'submitted_at' => now(),
        ]);

        $this->actingAs($user);

        Livewire::test(TakeQuiz::class, ['quiz' => $quiz])
            ->assertRedirect(route('quizzes.index'));
    }

    public function test_answer_is_saved_when_selected(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);
        $quiz = Quiz::factory()->create(['allow_multiple_attempts' => true]);

        $question = Question::factory()->create();
        $quiz->questions()->attach($question->id);

        $options = QuestionOption::factory()->count(4)->create([
            'question_id' => $question->id,
        ]);

        $selectedOption = $options->first();

        $this->actingAs($user);

        $component = Livewire::test(TakeQuiz::class, ['quiz' => $quiz]);

        $component->set("answers.{$question->id}", $selectedOption->id);

        $this->assertTrue(
            AttemptAnswer::where('question_id', $question->id)
                ->where('selected_option_id', $selectedOption->id)
                ->exists()
        );
    }

    public function test_navigation_to_next_question_works(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);
        $quiz = Quiz::factory()->create(['allow_multiple_attempts' => true]);

        $questions = Question::factory()->count(3)->create();
        $quiz->questions()->attach($questions->pluck('id'));

        foreach ($questions as $question) {
            QuestionOption::factory()->count(4)->create([
                'question_id' => $question->id,
            ]);
        }

        $this->actingAs($user);

        Livewire::test(TakeQuiz::class, ['quiz' => $quiz])
            ->assertSet('currentQuestionIndex', 0)
            ->call('nextQuestion')
            ->assertSet('currentQuestionIndex', 1)
            ->call('nextQuestion')
            ->assertSet('currentQuestionIndex', 2);
    }

    public function test_navigation_to_previous_question_works(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);
        $quiz = Quiz::factory()->create(['allow_multiple_attempts' => true]);

        $questions = Question::factory()->count(3)->create();
        $quiz->questions()->attach($questions->pluck('id'));

        foreach ($questions as $question) {
            QuestionOption::factory()->count(4)->create([
                'question_id' => $question->id,
            ]);
        }

        $this->actingAs($user);

        Livewire::test(TakeQuiz::class, ['quiz' => $quiz])
            ->set('currentQuestionIndex', 2)
            ->call('previousQuestion')
            ->assertSet('currentQuestionIndex', 1)
            ->call('previousQuestion')
            ->assertSet('currentQuestionIndex', 0);
    }

    public function test_go_to_specific_question_works(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);
        $quiz = Quiz::factory()->create(['allow_multiple_attempts' => true]);

        $questions = Question::factory()->count(5)->create();
        $quiz->questions()->attach($questions->pluck('id'));

        foreach ($questions as $question) {
            QuestionOption::factory()->count(4)->create([
                'question_id' => $question->id,
            ]);
        }

        $this->actingAs($user);

        Livewire::test(TakeQuiz::class, ['quiz' => $quiz])
            ->assertSet('currentQuestionIndex', 0)
            ->call('goToQuestion', 3)
            ->assertSet('currentQuestionIndex', 3)
            ->call('goToQuestion', 1)
            ->assertSet('currentQuestionIndex', 1);
    }

    public function test_cannot_navigate_beyond_question_bounds(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);
        $quiz = Quiz::factory()->create(['allow_multiple_attempts' => true]);

        $questions = Question::factory()->count(3)->create();
        $quiz->questions()->attach($questions->pluck('id'));

        foreach ($questions as $question) {
            QuestionOption::factory()->count(4)->create([
                'question_id' => $question->id,
            ]);
        }

        $this->actingAs($user);

        Livewire::test(TakeQuiz::class, ['quiz' => $quiz])
            ->set('currentQuestionIndex', 0)
            ->call('previousQuestion')
            ->assertSet('currentQuestionIndex', 0)
            ->set('currentQuestionIndex', 2)
            ->call('nextQuestion')
            ->assertSet('currentQuestionIndex', 2);
    }

    public function test_submit_quiz_calculates_correct_score(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);
        $quiz = Quiz::factory()->create(['allow_multiple_attempts' => true]);

        $question1 = Question::factory()->create();
        $question2 = Question::factory()->create();

        $quiz->questions()->attach([$question1->id, $question2->id]);

        $correctOption1 = QuestionOption::factory()->create([
            'question_id' => $question1->id,
            'is_correct' => true,
        ]);

        QuestionOption::factory()->count(3)->create([
            'question_id' => $question1->id,
            'is_correct' => false,
        ]);

        $correctOption2 = QuestionOption::factory()->create([
            'question_id' => $question2->id,
            'is_correct' => true,
        ]);

        $wrongOption2 = QuestionOption::factory()->create([
            'question_id' => $question2->id,
            'is_correct' => false,
        ]);

        QuestionOption::factory()->count(2)->create([
            'question_id' => $question2->id,
            'is_correct' => false,
        ]);

        $this->actingAs($user);

        $component = Livewire::test(TakeQuiz::class, ['quiz' => $quiz]);

        $component->set("answers.{$question1->id}", $correctOption1->id);
        $component->set("answers.{$question2->id}", $wrongOption2->id);

        $component->call('submitQuiz')
            ->assertRedirect();

        $attempt = Attempt::where('user_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->first();

        $this->assertNotNull($attempt);
        $this->assertNotNull($attempt->submitted_at);
        $this->assertSame(50.0, (float) $attempt->score);
        $this->assertSame(1, $attempt->correct_count);
        $this->assertSame(1, $attempt->wrong_count);
    }

    public function test_resume_in_progress_attempt(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);
        $quiz = Quiz::factory()->create(['allow_multiple_attempts' => true]);

        $question1 = Question::factory()->create();
        $question2 = Question::factory()->create();

        $quiz->questions()->attach([$question1->id, $question2->id]);

        $option1 = QuestionOption::factory()->create(['question_id' => $question1->id]);
        QuestionOption::factory()->count(3)->create(['question_id' => $question1->id]);
        QuestionOption::factory()->count(4)->create(['question_id' => $question2->id]);

        $existingAttempt = Attempt::create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'started_at' => now()->subMinutes(10),
        ]);

        AttemptAnswer::create([
            'attempt_id' => $existingAttempt->id,
            'question_id' => $question1->id,
            'selected_option_id' => $option1->id,
        ]);

        $this->actingAs($user);

        $component = Livewire::test(TakeQuiz::class, ['quiz' => $quiz]);

        $this->assertSame($existingAttempt->id, $component->get('attempt')->id);
        $this->assertArrayHasKey($question1->id, $component->get('answers'));
        $this->assertSame(
            $option1->id,
            $component->get("answers.{$question1->id}")
        );
    }

    public function test_timer_initializes_correctly_for_timed_quiz(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);
        $quiz = Quiz::factory()->create([
            'allow_multiple_attempts' => true,
            'time_limit_minutes' => 10,
        ]);

        $question = Question::factory()->create();
        $quiz->questions()->attach($question->id);

        QuestionOption::factory()->count(4)->create([
            'question_id' => $question->id,
        ]);

        $this->actingAs($user);

        $component = Livewire::test(TakeQuiz::class, ['quiz' => $quiz]);

        $remaining = $component->get('remainingSeconds');

        $this->assertNotNull($remaining);
        $this->assertGreaterThan(0, $remaining);
        $this->assertLessThanOrEqual(600, $remaining);
    }

    public function test_quiz_without_time_limit_has_null_remaining_seconds(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);
        $quiz = Quiz::factory()->create([
            'allow_multiple_attempts' => true,
            'time_limit_minutes' => null,
        ]);

        $question = Question::factory()->create();
        $quiz->questions()->attach($question->id);

        QuestionOption::factory()->count(4)->create([
            'question_id' => $question->id,
        ]);

        $this->actingAs($user);

        $component = Livewire::test(TakeQuiz::class, ['quiz' => $quiz]);

        $this->assertNull($component->get('remainingSeconds'));
    }

    public function test_questions_are_shuffled_when_shuffle_questions_is_enabled(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);
        $quiz = Quiz::factory()->create([
            'allow_multiple_attempts' => true,
            'shuffle_questions' => true,
        ]);

        $questions = Question::factory()->count(10)->create();
        $quiz->questions()->attach($questions->pluck('id'));

        foreach ($questions as $question) {
            QuestionOption::factory()->count(4)->create([
                'question_id' => $question->id,
            ]);
        }

        $this->actingAs($user);

        $component1 = Livewire::test(TakeQuiz::class, ['quiz' => $quiz]);
        $order1 = $component1->get('questions')->pluck('id')->toArray();

        $component2 = Livewire::test(TakeQuiz::class, ['quiz' => $quiz]);
        $order2 = $component2->get('questions')->pluck('id')->toArray();

        $this->assertNotSame($order1, $order2);
    }

    public function test_displays_quiz_title_and_question_progress(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);
        $quiz = Quiz::factory()->create([
            'title' => 'Advanced Laravel Quiz',
            'allow_multiple_attempts' => true,
        ]);

        $questions = Question::factory()->count(5)->create();
        $quiz->questions()->attach($questions->pluck('id'));

        foreach ($questions as $question) {
            QuestionOption::factory()->count(4)->create([
                'question_id' => $question->id,
            ]);
        }

        $this->actingAs($user);

        Livewire::test(TakeQuiz::class, ['quiz' => $quiz])
            ->assertSee('Advanced Laravel Quiz')
            ->assertSee(__('Question') . ' 1' . __(' of :count', ['count' => 5]));
    }
}
