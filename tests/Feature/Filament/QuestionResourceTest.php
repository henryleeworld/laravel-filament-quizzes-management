<?php

namespace Tests\Feature\Filament;

use App\Enums\Difficulty;
use App\Filament\Resources\Questions\Pages\CreateQuestion;
use App\Filament\Resources\Questions\Pages\EditQuestion;
use App\Filament\Resources\Questions\Pages\ListQuestions;
use App\Models\Category;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class QuestionResourceTest extends TestCase
{
    public function test_admin_can_view_questions_list_page(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(ListQuestions::class)
            ->assertSuccessful();
    }

    public function test_non_admin_cannot_view_questions_list_page(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $this->actingAs($user);

        Livewire::test(ListQuestions::class)
            ->assertForbidden();
    }

    public function test_admin_can_access_create_question_page(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(CreateQuestion::class)
            ->assertSuccessful();
    }

    public function test_admin_can_access_edit_question_page(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();
        $question = Question::factory()->create(['category_id' => $category->id]);

        $this->actingAs($admin);

        Livewire::test(EditQuestion::class, ['record' => $question->id])
            ->assertSuccessful();
    }

    public function test_admin_can_see_all_questions_in_table(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();

        $q1 = Question::factory()->create(['category_id' => $category->id, 'text' => 'What is PHP?']);
        $q2 = Question::factory()->create(['category_id' => $category->id, 'text' => 'What is Laravel?']);

        $this->actingAs($admin);

        Livewire::test(ListQuestions::class)
            ->assertCanSeeTableRecords([$q1, $q2])
            ->assertCountTableRecords(2);
    }

    public function test_admin_can_create_question_with_options(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();

        $this->actingAs($admin);

        $question = Question::create([
            'category_id' => $category->id,
            'text' => 'What is Laravel?',
            'explanation' => '<p>Laravel is a PHP framework.</p>',
            'difficulty' => Difficulty::Easy,
        ]);

        QuestionOption::create([
            'question_id' => $question->id,
            'text' => 'A PHP framework',
            'is_correct' => true,
            'order' => 0,
        ]);

        QuestionOption::create([
            'question_id' => $question->id,
            'text' => 'A JavaScript library',
            'is_correct' => false,
            'order' => 1,
        ]);

        QuestionOption::create([
            'question_id' => $question->id,
            'text' => 'A database',
            'is_correct' => false,
            'order' => 2,
        ]);

        $this->assertDatabaseHas('questions', [
            'text' => 'What is Laravel?',
            'difficulty' => Difficulty::Easy->value,
        ]);

        $this->assertDatabaseHas('question_options', [
            'text' => 'A PHP framework',
            'is_correct' => true,
        ]);

        $this->assertCount(3, $question->fresh()->options);
    }

    public function test_admin_can_update_question(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();

        $question = Question::factory()->create([
            'category_id' => $category->id,
            'text' => 'Old question?',
            'difficulty' => Difficulty::Easy,
        ]);

        $option1 = QuestionOption::factory()->create(['question_id' => $question->id, 'text' => 'Option 1', 'is_correct' => true, 'order' => 0]);
        $option2 = QuestionOption::factory()->create(['question_id' => $question->id, 'text' => 'Option 2', 'is_correct' => false, 'order' => 1]);

        $this->actingAs($admin);

        $livewire = Livewire::test(EditQuestion::class, ['record' => $question->id]);
        $data = $livewire->get('data');
        $data['text'] = 'Updated question?';

        $livewire
            ->fillForm($data)
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('questions', [
            'id' => $question->id,
            'text' => 'Updated question?',
        ]);
    }

    public function test_admin_can_delete_question_and_cascade_options(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();

        $question = Question::factory()->create(['category_id' => $category->id]);
        $option = QuestionOption::factory()->create(['question_id' => $question->id]);

        $this->actingAs($admin);

        Livewire::test(EditQuestion::class, ['record' => $question->id])
            ->callAction('delete');

        $this->assertDatabaseMissing('questions', ['id' => $question->id]);
        $this->assertDatabaseMissing('question_options', ['id' => $option->id]);
    }

    public function test_admin_can_search_questions(): void
    {
        $admin = User::factory()->admin()->create();

        $q1 = Question::factory()->create(['text' => 'What is Laravel framework?']);
        $q2 = Question::factory()->create(['text' => 'What is PHP programming?']);

        $this->actingAs($admin);

        Livewire::test(ListQuestions::class)
            ->searchTable('Laravel')
            ->assertCanSeeTableRecords([$q1])
            ->assertCanNotSeeTableRecords([$q2]);
    }

    public function test_admin_can_filter_by_category_and_difficulty(): void
    {
        $admin = User::factory()->admin()->create();

        $category = Category::factory()->create();
        $easy = Question::factory()->easy()->create(['category_id' => $category->id]);
        $hard = Question::factory()->hard()->create();

        $this->actingAs($admin);

        Livewire::test(ListQuestions::class)
            ->filterTable('category', $category->id)
            ->filterTable('difficulty', Difficulty::Easy->value)
            ->assertCanSeeTableRecords([$easy])
            ->assertCanNotSeeTableRecords([$hard]);
    }

    public function test_table_renders_expected_columns(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(ListQuestions::class)
            ->assertCanRenderTableColumn('text')
            ->assertCanRenderTableColumn('category.name')
            ->assertCanRenderTableColumn('difficulty')
            ->assertCanRenderTableColumn('image_path')
            ->assertCanRenderTableColumn('options_count')
            ->assertCanRenderTableColumn('created_at');
    }

    public function test_create_form_validations_work(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(CreateQuestion::class)
            ->fillForm([
                'category_id' => null,
                'text' => '',
                'difficulty' => null,
                'options' => [
                    ['text' => '', 'is_correct' => false],
                ],
            ])
            ->call('create')
            ->assertHasFormErrors([
                'category_id' => 'required',
                'text' => 'required',
                'difficulty' => 'required',
                'options.0.text' => 'required',
            ]);
    }
}
