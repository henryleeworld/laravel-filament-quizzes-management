<?php

namespace Tests\Feature\Filament\Widgets;

use App\Filament\Widgets\QuestionStatsWidget;
use App\Filament\Widgets\RecentAttemptsWidget;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Models\Attempt;
use App\Models\AttemptAnswer;
use App\Models\Category;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class AdminDashboardWidgetsTest extends TestCase
{
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    public function test_stats_overview_widget_renders_for_admin(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(StatsOverviewWidget::class)
            ->assertSuccessful();
    }

    public function test_stats_overview_widget_shows_correct_total_students_count(): void
    {
        User::factory()->count(5)->create(['is_admin' => false]);
        User::factory()->count(2)->create(['is_admin' => true]);

        $this->actingAs($this->admin);

        Livewire::test(StatsOverviewWidget::class)
            ->assertSee('5')
            ->assertSee(__('Total students'));
    }

    public function test_stats_overview_widget_shows_correct_total_quizzes_count(): void
    {
        Quiz::factory()->count(3)->create();

        $this->actingAs($this->admin);

        Livewire::test(StatsOverviewWidget::class)
            ->assertSee('3')
            ->assertSee(__('Total quizzes'));
    }

    public function test_stats_overview_widget_shows_correct_total_attempts_count(): void
    {
        $quiz = Quiz::factory()->create();
        $user = User::factory()->create();

        Attempt::factory()->count(5)->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'submitted_at' => now(),
        ]);

        Attempt::factory()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'submitted_at' => null,
        ]);

        $this->actingAs($this->admin);

        Livewire::test(StatsOverviewWidget::class)
            ->assertSee('5')
            ->assertSee(__('Total attempts'));
    }

    public function test_stats_overview_widget_shows_correct_average_score(): void
    {
        $quiz = Quiz::factory()->create();
        $user = User::factory()->create();

        Attempt::factory()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'submitted_at' => now(),
            'score' => 80.00,
        ]);

        Attempt::factory()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'submitted_at' => now(),
            'score' => 60.00,
        ]);

        $this->actingAs($this->admin);

        Livewire::test(StatsOverviewWidget::class)
            ->assertSee('70.0%')
            ->assertSee(__('Average score'));
    }

    public function test_recent_attempts_widget_renders_for_admin(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(RecentAttemptsWidget::class)
            ->assertSuccessful();
    }

    public function test_recent_attempts_widget_shows_attempts_in_table(): void
    {
        $quiz = Quiz::factory()->create(['title' => 'Laravel Basics']);
        $user = User::factory()->create(['name' => 'John Student']);

        Attempt::factory()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'submitted_at' => now(),
            'score' => 85.00,
        ]);

        $this->actingAs($this->admin);

        Livewire::test(RecentAttemptsWidget::class)
            ->assertSee('John Student')
            ->assertSee('Laravel Basics')
            ->assertSee('85%')
            ->assertSee(__('Passed'));
    }

    public function test_recent_attempts_widget_shows_failed_status_for_low_scores(): void
    {
        $quiz = Quiz::factory()->create();
        $user = User::factory()->create();

        Attempt::factory()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'submitted_at' => now(),
            'score' => 50.00,
        ]);

        $this->actingAs($this->admin);

        Livewire::test(RecentAttemptsWidget::class)
            ->assertSee('50%')
            ->assertSee(__('Failed'));
    }

    public function test_recent_attempts_widget_only_shows_completed_attempts(): void
    {
        $quiz = Quiz::factory()->create(['title' => 'Completed Quiz']);
        $quizInProgress = Quiz::factory()->create(['title' => 'In Progress Quiz']);
        $user = User::factory()->create();

        Attempt::factory()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'submitted_at' => now(),
            'score' => 75.00,
        ]);

        Attempt::factory()->create([
            'user_id' => $user->id,
            'quiz_id' => $quizInProgress->id,
            'submitted_at' => null,
        ]);

        $this->actingAs($this->admin);

        Livewire::test(RecentAttemptsWidget::class)
            ->assertSee('Completed Quiz')
            ->assertDontSee('In Progress Quiz');
    }

    public function test_question_stats_widget_renders_for_admin(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(QuestionStatsWidget::class)
            ->assertSuccessful();
    }

    public function test_question_stats_widget_shows_questions_in_table(): void
    {
        $category = Category::factory()->create(['name' => 'PHP']);

        Question::factory()->create([
            'category_id' => $category->id,
            'text' => 'What is PHP?',
        ]);

        $this->actingAs($this->admin);

        Livewire::test(QuestionStatsWidget::class)
            ->assertSee('What is PHP?')
            ->assertSee('PHP');
    }

    public function test_question_stats_widget_shows_times_answered_count(): void
    {
        $question = Question::factory()->create(['text' => 'Test Question']);
        $quiz = Quiz::factory()->create();
        $user = User::factory()->create();

        $attempt = Attempt::factory()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'submitted_at' => now(),
        ]);

        AttemptAnswer::factory()->count(3)->create([
            'attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'is_correct' => true,
        ]);

        $this->actingAs($this->admin);

        Livewire::test(QuestionStatsWidget::class)
            ->assertSee('Test Question')
            ->assertSee('3');
    }

    public function test_question_stats_widget_shows_percent_correct(): void
    {
        $question = Question::factory()->create(['text' => 'Sample Question']);
        $quiz = Quiz::factory()->create();
        $user = User::factory()->create();

        $attempt = Attempt::factory()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'submitted_at' => now(),
        ]);

        AttemptAnswer::factory()->create([
            'attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'is_correct' => true,
        ]);

        AttemptAnswer::factory()->create([
            'attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'is_correct' => false,
        ]);

        $this->actingAs($this->admin);

        Livewire::test(QuestionStatsWidget::class)
            ->assertSee('Sample Question')
            ->assertSee('50%');
    }

    public function test_question_stats_widget_shows_dash_when_no_answers(): void
    {
        Question::factory()->create(['text' => 'Unanswered Question']);

        $this->actingAs($this->admin);

        Livewire::test(QuestionStatsWidget::class)
            ->assertSee('Unanswered Question')
            ->assertSee('-');
    }

    public function test_question_stats_widget_shows_difficulty_badge(): void
    {
        Question::factory()->create([
            'text' => 'Easy Question',
            'difficulty' => 'easy',
        ]);

        $this->actingAs($this->admin);

        Livewire::test(QuestionStatsWidget::class)
            ->assertSee('Easy Question')
            ->assertSee(__('Easy'));
    }
}
