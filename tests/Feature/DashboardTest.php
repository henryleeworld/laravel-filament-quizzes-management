<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Models\Attempt;
use App\Models\AttemptAnswer;
use App\Models\Category;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    public function test_guests_are_redirected_to_login_page(): void
    {
        $this->get('/dashboard')
            ->assertRedirect('/login');
    }

    public function test_authenticated_users_can_visit_dashboard(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk();
    }

    public function test_dashboard_displays_zero_stats_when_user_has_no_attempts(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);
        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->assertSee(__('Total attempts'))
            ->assertSee('0')
            ->assertSee(__('Average score'))
            ->assertSee('0%')
            ->assertSee(__('Pass rate'))
            ->assertSee(__('No attempts yet'));
    }

    public function test_dashboard_calculates_stats_correctly_with_attempts(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);
        $quiz = Quiz::factory()->create();

        Attempt::factory()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'submitted_at' => now(),
            'score' => 80.00,
            'correct_count' => 8,
            'wrong_count' => 2,
        ]);

        Attempt::factory()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'submitted_at' => now(),
            'score' => 60.00,
            'correct_count' => 6,
            'wrong_count' => 4,
        ]);

        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->assertSeeInOrder([__('Total attempts'), '2'])
            ->assertSee('70%')
            ->assertSee('50%');
    }

    public function test_dashboard_only_shows_completed_attempts(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);
        $quiz = Quiz::factory()->create();

        Attempt::factory()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'submitted_at' => now(),
            'score' => 80.00,
        ]);

        Attempt::factory()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'submitted_at' => null,
            'score' => null,
        ]);

        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->assertSeeInOrder([__('Total attempts'), '1']);
    }

    public function test_dashboard_does_not_show_other_users_attempts(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);
        $otherUser = User::factory()->create(['email' => 'other@example.com']);
        $quiz = Quiz::factory()->create(['title' => 'Other Users Quiz']);

        Attempt::factory()->create([
            'user_id' => $otherUser->id,
            'quiz_id' => $quiz->id,
            'submitted_at' => now(),
            'score' => 95.00,
        ]);

        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->assertDontSee('Other Users Quiz')
            ->assertDontSee('95%');
    }

    public function test_attempt_history_displays_quiz_title_and_score(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);
        $quiz = Quiz::factory()->create(['title' => 'Laravel Fundamentals']);

        Attempt::factory()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'submitted_at' => now(),
            'score' => 85.00,
            'correct_count' => 17,
            'wrong_count' => 3,
            'time_taken_seconds' => 1200,
        ]);

        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->assertSee('Laravel Fundamentals')
            ->assertSee('85%')
            ->assertSee('17/3')
            ->assertSee('20:00')
            ->assertSee(__('Passed'));
    }

    public function test_attempt_history_shows_failed_badge_for_low_scores(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);
        $quiz = Quiz::factory()->create(['title' => 'Difficult Quiz']);

        Attempt::factory()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'submitted_at' => now(),
            'score' => 50.00,
        ]);

        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->assertSee('Difficult Quiz')
            ->assertSee('50%')
            ->assertSee(__('Failed'));
    }

    public function test_empty_state_shows_browse_quizzes_button(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);
        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->assertSee(__('No attempts yet'))
            ->assertSee(__('Browse quizzes'));
    }

    public function test_category_performance_is_displayed_correctly(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);
        $category = Category::factory()->create(['name' => 'PHP Basics']);
        $quiz = Quiz::factory()->create();

        $question1 = Question::factory()->create(['category_id' => $category->id]);
        $question2 = Question::factory()->create(['category_id' => $category->id]);

        $attempt = Attempt::factory()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'submitted_at' => now(),
            'score' => 50.00,
        ]);

        AttemptAnswer::factory()->create([
            'attempt_id' => $attempt->id,
            'question_id' => $question1->id,
            'is_correct' => true,
        ]);

        AttemptAnswer::factory()->create([
            'attempt_id' => $attempt->id,
            'question_id' => $question2->id,
            'is_correct' => false,
        ]);

        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->assertSee(__('Category performance'))
            ->assertSee('PHP Basics')
            ->assertSee('2')
            ->assertSee('50%');
    }

    public function test_category_performance_is_hidden_when_no_answers_exist(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);
        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->assertDontSee(__('Category performance'));
    }

    public function test_pass_rate_calculates_correctly_with_mixed_results(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);
        $quiz = Quiz::factory()->create();

        Attempt::factory()->create(['user_id' => $user->id, 'quiz_id' => $quiz->id, 'submitted_at' => now(), 'score' => 80]);
        Attempt::factory()->create(['user_id' => $user->id, 'quiz_id' => $quiz->id, 'submitted_at' => now(), 'score' => 70]);
        Attempt::factory()->create(['user_id' => $user->id, 'quiz_id' => $quiz->id, 'submitted_at' => now(), 'score' => 60]);
        Attempt::factory()->create(['user_id' => $user->id, 'quiz_id' => $quiz->id, 'submitted_at' => now(), 'score' => 50]);

        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->assertSee('50%');
    }

    public function test_attempt_history_links_to_detailed_results_page(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);
        $quiz = Quiz::factory()->create(['title' => 'Test Quiz']);

        $attempt = Attempt::factory()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'submitted_at' => now(),
            'score' => 75.00,
        ]);

        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->assertSee(__('View'))
            ->assertSeeHtml(route('attempts.results', $attempt));
    }
}
