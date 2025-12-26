<?php

namespace Tests\Feature\Quizzes;

use App\Livewire\Quizzes\QuizResults;
use App\Models\Attempt;
use App\Models\AttemptAnswer;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class QuizResultsTest extends TestCase
{
    public function test_guests_are_redirected_to_login_page(): void
    {
        $quiz = Quiz::factory()->create();
        $user = User::factory()->create();

        $attempt = Attempt::factory()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'submitted_at' => now(),
        ]);

        $this->get(route('attempts.results', $attempt))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_their_own_quiz_results(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);
        $quiz = Quiz::factory()->create(['title' => 'Laravel Fundamentals']);

        $attempt = Attempt::factory()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'submitted_at' => now(),
            'score' => 85.00,
            'correct_count' => 17,
            'wrong_count' => 3,
        ]);

        $this->actingAs($user)
            ->get(route('attempts.results', $attempt))
            ->assertOk();
    }

    public function test_users_cannot_view_other_users_quiz_results(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);
        $otherUser = User::factory()->create(['email' => 'other@example.com']);
        $quiz = Quiz::factory()->create();

        $attempt = Attempt::factory()->create([
            'user_id' => $otherUser->id,
            'quiz_id' => $quiz->id,
            'submitted_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('attempts.results', $attempt))
            ->assertForbidden();
    }

    public function test_results_display_score_percentage_correctly(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);
        $quiz = Quiz::factory()->create(['title' => 'PHP Basics']);

        $attempt = Attempt::factory()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'submitted_at' => now(),
            'score' => 92.50,
            'correct_count' => 19,
            'wrong_count' => 1,
        ]);

        $this->actingAs($user);

        Livewire::test(QuizResults::class, ['attempt' => $attempt])
            ->assertSee('93%')
            ->assertSee('19 ' . __('correct'))
            ->assertSee('1 ' . __('wrong'));
    }

    public function test_results_display_passed_badge_when_score_is_70_or_above(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);
        $quiz = Quiz::factory()->create();

        $attempt = Attempt::factory()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'submitted_at' => now(),
            'score' => 75.00,
        ]);

        $this->actingAs($user);

        Livewire::test(QuizResults::class, ['attempt' => $attempt])
            ->assertSee(__('Passed'));
    }

    public function test_results_display_failed_badge_when_score_is_below_70(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);
        $quiz = Quiz::factory()->create();

        $attempt = Attempt::factory()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'submitted_at' => now(),
            'score' => 55.00,
        ]);

        $this->actingAs($user);

        Livewire::test(QuizResults::class, ['attempt' => $attempt])
            ->assertSee(__('Failed'));
    }

    public function test_results_display_time_taken_when_available(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);
        $quiz = Quiz::factory()->create();

        $attempt = Attempt::factory()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'submitted_at' => now(),
            'time_taken_seconds' => 1800,
        ]);

        $this->actingAs($user);

        Livewire::test(QuizResults::class, ['attempt' => $attempt])
            ->assertSee(__('Time: '))
            ->assertSee('30:00');
    }

    public function test_results_display_question_breakdown_with_correct_answers(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);
        $quiz = Quiz::factory()->create(['title' => 'Laravel Fundamentals']);

        $question = Question::factory()->create([
            'text' => 'What is Laravel?',
        ]);

        $correctOption = QuestionOption::factory()->create([
            'question_id' => $question->id,
            'text' => 'A PHP framework',
            'is_correct' => true,
        ]);

        QuestionOption::factory()->create([
            'question_id' => $question->id,
            'text' => 'A database',
            'is_correct' => false,
        ]);

        $quiz->questions()->attach($question->id);

        $attempt = Attempt::factory()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'submitted_at' => now(),
            'score' => 100.00,
            'correct_count' => 1,
            'wrong_count' => 0,
        ]);

        AttemptAnswer::factory()->create([
            'attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'selected_option_id' => $correctOption->id,
            'is_correct' => true,
        ]);

        $this->actingAs($user);

        Livewire::test(QuizResults::class, ['attempt' => $attempt])
            ->assertSee('What is Laravel?')
            ->assertSee('A PHP framework')
            ->assertSee(__('Your answer'))
            ->assertSee(__('Correct'));
    }

    public function test_results_display_question_breakdown_with_wrong_answers(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);
        $quiz = Quiz::factory()->create(['title' => 'Laravel Fundamentals']);

        $question = Question::factory()->create([
            'text' => 'What is Laravel?',
        ]);

        $correctOption = QuestionOption::factory()->create([
            'question_id' => $question->id,
            'text' => 'A PHP framework',
            'is_correct' => true,
        ]);

        $wrongOption = QuestionOption::factory()->create([
            'question_id' => $question->id,
            'text' => 'A database',
            'is_correct' => false,
        ]);

        $quiz->questions()->attach($question->id);

        $attempt = Attempt::factory()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'submitted_at' => now(),
            'score' => 0.00,
            'correct_count' => 0,
            'wrong_count' => 1,
        ]);

        AttemptAnswer::factory()->create([
            'attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'selected_option_id' => $wrongOption->id,
            'is_correct' => false,
        ]);

        $this->actingAs($user);

        Livewire::test(QuizResults::class, ['attempt' => $attempt])
            ->assertSee('What is Laravel?')
            ->assertSee('A database')
            ->assertSee(__('Your answer'))
            ->assertSee('A PHP framework')
            ->assertSee(__('Correct'));
    }

    public function test_results_display_explanation_when_available(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);
        $quiz = Quiz::factory()->create();

        $question = Question::factory()->create([
            'text' => 'What is Eloquent?',
            'explanation' => 'Eloquent is Laravel\'s built-in ORM for database operations.',
        ]);

        $correctOption = QuestionOption::factory()->create([
            'question_id' => $question->id,
            'text' => 'An ORM',
            'is_correct' => true,
        ]);

        $quiz->questions()->attach($question->id);

        $attempt = Attempt::factory()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'submitted_at' => now(),
        ]);

        AttemptAnswer::factory()->create([
            'attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'selected_option_id' => $correctOption->id,
            'is_correct' => true,
        ]);

        $this->actingAs($user);

        Livewire::test(QuizResults::class, ['attempt' => $attempt])
            ->assertSee(__('Explanation: '))
            ->assertSee('Eloquent is Laravel\'s built-in ORM for database operations.');
    }

    public function test_retake_button_shows_when_quiz_allows_multiple_attempts(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);

        $quiz = Quiz::factory()->create([
            'title' => 'Retakeable Quiz',
            'allow_multiple_attempts' => true,
        ]);

        $attempt = Attempt::factory()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'submitted_at' => now(),
            'score' => 60.00,
        ]);

        $this->actingAs($user);

        Livewire::test(QuizResults::class, ['attempt' => $attempt])
            ->assertSee(__('Retake quiz'));
    }

    public function test_retake_button_does_not_show_when_quiz_does_not_allow_multiple_attempts(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);

        $quiz = Quiz::factory()->create([
            'title' => 'One Time Quiz',
            'allow_multiple_attempts' => false,
        ]);

        $attempt = Attempt::factory()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'submitted_at' => now(),
            'score' => 60.00,
        ]);

        $this->actingAs($user);

        Livewire::test(QuizResults::class, ['attempt' => $attempt])
            ->assertDontSee('Retake Quiz');
    }

    public function test_retake_button_redirects_to_take_quiz_page(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);
        $quiz = Quiz::factory()->create(['allow_multiple_attempts' => true]);

        $attempt = Attempt::factory()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'submitted_at' => now(),
        ]);

        $this->actingAs($user);

        Livewire::test(QuizResults::class, ['attempt' => $attempt])
            ->call('retakeQuiz')
            ->assertRedirect(route('quizzes.take', $quiz));
    }

    public function test_back_to_quizzes_button_is_always_visible(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);
        $quiz = Quiz::factory()->create();

        $attempt = Attempt::factory()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'submitted_at' => now(),
        ]);

        $this->actingAs($user);

        Livewire::test(QuizResults::class, ['attempt' => $attempt])
            ->assertSee(__('Back to quizzes'));
    }

    public function test_quiz_title_is_displayed_in_results(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);

        $quiz = Quiz::factory()->create([
            'title' => 'Advanced PHP Concepts',
        ]);

        $attempt = Attempt::factory()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'submitted_at' => now(),
        ]);

        $this->actingAs($user);

        Livewire::test(QuizResults::class, ['attempt' => $attempt])
            ->assertSee('Advanced PHP Concepts');
    }

    public function test_multiple_questions_display_in_correct_order(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);
        $quiz = Quiz::factory()->create();

        $question1 = Question::factory()->create(['text' => 'First Question']);
        $question2 = Question::factory()->create(['text' => 'Second Question']);
        $question3 = Question::factory()->create(['text' => 'Third Question']);

        $quiz->questions()->attach([
            $question1->id,
            $question2->id,
            $question3->id,
        ]);

        $attempt = Attempt::factory()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'submitted_at' => now(),
        ]);

        $this->actingAs($user);

        Livewire::test(QuizResults::class, ['attempt' => $attempt])
            ->assertSee('First Question')
            ->assertSee('Second Question')
            ->assertSee('Third Question')
            ->assertSee(__('Question') . ' 1')
            ->assertSee(__('Question') . ' 2')
            ->assertSee(__('Question') . ' 3');
    }
}
