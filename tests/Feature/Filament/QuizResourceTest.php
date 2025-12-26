<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Quizzes\Pages\CreateQuiz;
use App\Filament\Resources\Quizzes\Pages\EditQuiz;
use App\Filament\Resources\Quizzes\Pages\ListQuizzes;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class QuizResourceTest extends TestCase
{
    public function test_admin_can_view_quizzes_list_page(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        Livewire::test(ListQuizzes::class)
            ->assertSuccessful();
    }

    public function test_non_admin_cannot_view_quizzes_list_page(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user);

        Livewire::test(ListQuizzes::class)
            ->assertForbidden();
    }

    public function test_admin_can_access_create_quiz_page(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        Livewire::test(CreateQuiz::class)
            ->assertSuccessful();
    }

    public function test_admin_can_access_edit_quiz_page(): void
    {
        $admin = User::factory()->admin()->create();
        $quiz = Quiz::factory()->create();

        $this->actingAs($admin);

        Livewire::test(EditQuiz::class, ['record' => $quiz->id])
            ->assertSuccessful();
    }

    public function test_admin_can_see_all_quizzes_in_table(): void
    {
        $admin = User::factory()->admin()->create();
        $quiz1 = Quiz::factory()->create(['title' => 'Laravel Basics Quiz']);
        $quiz2 = Quiz::factory()->create(['title' => 'PHP Advanced Quiz']);

        $this->actingAs($admin);

        Livewire::test(ListQuizzes::class)
            ->assertCanSeeTableRecords([$quiz1, $quiz2])
            ->assertCountTableRecords(2);
    }

    public function test_admin_can_create_quiz_with_questions(): void
    {
        $admin = User::factory()->admin()->create();
        $questions = Question::factory()->count(5)->create();

        $this->actingAs($admin);

        Livewire::test(CreateQuiz::class)
            ->fillForm([
                'title' => 'Laravel Basics',
                'description' => 'Test your Laravel knowledge',
                'question_count' => 5,
                'time_limit_minutes' => 30,
                'shuffle_questions' => true,
                'shuffle_answers' => false,
                'allow_multiple_attempts' => true,
                'questions' => $questions->pluck('id')->toArray(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('quizzes', [
            'title' => 'Laravel Basics',
            'description' => 'Test your Laravel knowledge',
            'question_count' => 5,
            'time_limit_minutes' => 30,
            'shuffle_questions' => true,
            'shuffle_answers' => false,
            'allow_multiple_attempts' => true,
        ]);

        $quiz = Quiz::where('title', 'Laravel Basics')->first();
        $this->assertCount(5, $quiz->questions);
    }

    public function test_admin_can_update_quiz(): void
    {
        $admin = User::factory()->admin()->create();
        $quiz = Quiz::factory()->create([
            'title' => 'Old Quiz Title',
            'question_count' => 3,
        ]);

        $questions = Question::factory()->count(3)->create();
        $quiz->questions()->attach($questions->pluck('id'));

        $this->actingAs($admin);

        $livewire = Livewire::test(EditQuiz::class, ['record' => $quiz->id]);
        $data = $livewire->get('data');
        $data['title'] = 'Updated Quiz Title';

        $livewire->fillForm($data)
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('quizzes', [
            'id' => $quiz->id,
            'title' => 'Updated Quiz Title',
        ]);
    }

    public function test_admin_can_delete_quiz(): void
    {
        $admin = User::factory()->admin()->create();
        $quiz = Quiz::factory()->create();

        $this->actingAs($admin);

        Livewire::test(EditQuiz::class, ['record' => $quiz->id])
            ->callAction('delete');

        $this->assertDatabaseMissing('quizzes', ['id' => $quiz->id]);
    }

    public function test_admin_can_search_quizzes_by_title(): void
    {
        $admin = User::factory()->admin()->create();
        $quiz1 = Quiz::factory()->create(['title' => 'Laravel Framework Quiz']);
        $quiz2 = Quiz::factory()->create(['title' => 'PHP Programming Quiz']);

        $this->actingAs($admin);

        Livewire::test(ListQuizzes::class)
            ->searchTable('Laravel')
            ->assertCanSeeTableRecords([$quiz1])
            ->assertCanNotSeeTableRecords([$quiz2]);
    }

    public function test_table_renders_all_expected_columns(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        Livewire::test(ListQuizzes::class)
            ->assertCanRenderTableColumn('title')
            ->assertCanRenderTableColumn('question_count')
            ->assertCanRenderTableColumn('time_limit_minutes')
            ->assertCanRenderTableColumn('shuffle_questions')
            ->assertCanRenderTableColumn('shuffle_answers')
            ->assertCanRenderTableColumn('allow_multiple_attempts')
            ->assertCanRenderTableColumn('attempts_count')
            ->assertCanRenderTableColumn('created_at');
    }

    public function test_deleting_quiz_does_not_cascade_to_questions(): void
    {
        $admin = User::factory()->admin()->create();
        $quiz = Quiz::factory()->create();
        $question = Question::factory()->create();

        $quiz->questions()->attach($question->id);

        $this->actingAs($admin);

        Livewire::test(EditQuiz::class, ['record' => $quiz->id])
            ->callAction('delete');

        $this->assertDatabaseMissing('quizzes', ['id' => $quiz->id]);
        $this->assertDatabaseHas('questions', ['id' => $question->id]);
    }
}
