<?php

namespace Tests\Feature\Quizzes;

use App\Livewire\Quizzes\QuizList;
use App\Models\Attempt;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class QuizListTest extends TestCase
{
    public function test_guests_are_redirected_to_login_page(): void
    {
        $this->get(route('quizzes.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_quiz_list_page(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);

        $this->actingAs($user)
            ->get(route('quizzes.index'))
            ->assertOk();
    }

    public function test_quizzes_display_correctly_with_question_counts(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);

        $quiz = Quiz::factory()->create([
            'title' => 'Laravel Fundamentals',
            'description' => 'Test your Laravel knowledge',
        ]);

        $questions = Question::factory()->count(5)->create();
        $quiz->questions()->attach($questions->pluck('id'));

        $this->actingAs($user);

        Livewire::test(QuizList::class)
            ->assertSee('Laravel Fundamentals')
            ->assertSee('Test your Laravel knowledge')
            ->assertSee('5 ' . __('Questions'));
    }

    public function test_quiz_displays_time_limit_when_set(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);

        Quiz::factory()->create([
            'title' => 'Timed Quiz',
            'time_limit_minutes' => 30,
        ]);

        $this->actingAs($user);

        Livewire::test(QuizList::class)
            ->assertSee('30 ' . __('min'));
    }

    public function test_quiz_displays_retake_badge_when_multiple_attempts_allowed(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);

        Quiz::factory()->create([
            'title' => 'Retakeable Quiz',
            'allow_multiple_attempts' => true,
        ]);

        $this->actingAs($user);

        Livewire::test(QuizList::class)
            ->assertSee(__('Retakes allowed'));
    }

    public function test_user_sees_own_completed_attempts_with_scores(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);

        $quiz = Quiz::factory()->create(['title' => 'PHP Basics']);
        $questions = Question::factory()->count(5)->create();
        $quiz->questions()->attach($questions->pluck('id'));

        Attempt::factory()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'started_at' => now()->subHour(),
            'submitted_at' => now()->subMinutes(30),
            'score' => 85.50,
        ]);

        $this->actingAs($user);

        Livewire::test(QuizList::class)
            ->assertSee(__('Last attempt'))
            ->assertSee(__('Score: ') . '86%');
    }

    public function test_user_does_not_see_other_users_attempts(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);
        $otherUser = User::factory()->create(['email' => 'other@example.com']);

        $quiz = Quiz::factory()->create(['title' => 'PHP Basics']);

        Attempt::factory()->create([
            'user_id' => $otherUser->id,
            'quiz_id' => $quiz->id,
            'submitted_at' => now(),
            'score' => 95.00,
        ]);

        $this->actingAs($user);

        Livewire::test(QuizList::class)
            ->assertDontSee('Last Attempt')
            ->assertDontSee('Score: 95%');
    }

    public function test_start_button_is_enabled_when_user_can_attempt_quiz(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);

        Quiz::factory()->create([
            'title' => 'New Quiz',
            'allow_multiple_attempts' => true,
        ]);

        $this->actingAs($user);

        Livewire::test(QuizList::class)
            ->assertSee(__('Start quiz'));
    }

    public function test_start_button_is_disabled_when_quiz_does_not_allow_retakes_and_user_has_completed(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);

        $quiz = Quiz::factory()->create([
            'title' => 'One Time Quiz',
            'allow_multiple_attempts' => false,
        ]);

        Attempt::factory()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'started_at' => now()->subHour(),
            'submitted_at' => now(),
            'score' => 75.00,
        ]);

        $this->actingAs($user);

        Livewire::test(QuizList::class)
            ->assertSee(__('Already completed'));
    }

    public function test_retake_button_shows_when_user_has_completed_and_retakes_are_allowed(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);

        $quiz = Quiz::factory()->create([
            'title' => 'Retakeable Quiz',
            'allow_multiple_attempts' => true,
        ]);

        Attempt::factory()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'started_at' => now()->subHour(),
            'submitted_at' => now(),
            'score' => 60.00,
        ]);

        $this->actingAs($user);

        Livewire::test(QuizList::class)
            ->assertSee(__('Retake quiz'));
    }

    public function test_start_quiz_redirects_to_take_quiz_page(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);
        $quiz = Quiz::factory()->create(['allow_multiple_attempts' => true]);

        $this->actingAs($user);

        Livewire::test(QuizList::class)
            ->call('startQuiz', $quiz->id)
            ->assertRedirect(route('quizzes.take', $quiz));
    }

    public function test_start_quiz_shows_error_when_user_cannot_attempt_quiz(): void
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

        Livewire::test(QuizList::class)
            ->call('startQuiz', $quiz->id)
            ->assertDispatched('notify');
    }

    public function test_shows_message_when_no_quizzes_are_available(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);

        $this->actingAs($user);

        Livewire::test(QuizList::class)
            ->assertSee(__('No quizzes available at this time.'));
    }

    public function test_displays_multiple_quizzes_in_grid_layout(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);

        Quiz::factory()->create(['title' => 'Quiz One']);
        Quiz::factory()->create(['title' => 'Quiz Two']);
        Quiz::factory()->create(['title' => 'Quiz Three']);

        $this->actingAs($user);

        Livewire::test(QuizList::class)
            ->assertSee('Quiz One')
            ->assertSee('Quiz Two')
            ->assertSee('Quiz Three');
    }
}
