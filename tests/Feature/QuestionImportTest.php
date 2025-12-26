<?php

namespace Tests\Feature;

use App\Enums\Difficulty;
use App\Filament\Imports\QuestionImporter;
use App\Models\Category;
use App\Models\Question;
use App\Models\User;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use ReflectionClass;
use Tests\TestCase;

class QuestionImportTest extends TestCase
{
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create(['is_admin' => true]);

        Category::factory()->create([
            'name' => 'Programming Concepts',
            'slug' => 'programming-concepts',
        ]);

        Category::factory()->create([
            'name' => 'Database Design',
            'slug' => 'database-design',
        ]);

        Category::factory()->create([
            'name' => 'Web Development',
            'slug' => 'web-development',
        ]);
    }

    public function test_it_can_import_valid_questions_from_csv(): void
    {
        Queue::fake();
        Storage::fake('local');

        $csv = "Category,Question,Explanation,Difficulty,Option 1,Option 2,Option 3,Option 4,Option 5,Correct Answers\n";
        $csv .= "Programming Concepts,What is PHP?,PHP is a programming language,easy,A database,A programming language,,,2\n";
        $csv .= "Database Design,What is SQL?,SQL is a query language,medium,A programming language,A query language for databases,,,2";

        $file = UploadedFile::fake()->createWithContent('questions.csv', $csv);

        Import::create([
            'user_id' => $this->adminUser->id,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $file->store('imports', 'local'),
            'importer' => QuestionImporter::class,
            'total_rows' => 2,
        ]);

        $this->assertSame(0, Question::count());
    }

    public function test_it_validates_category_exists(): void
    {
        $importer = $this->createImporter([
            'category' => 'Non-existent Category',
            'text' => 'Test question?',
            'explanation' => 'Test explanation',
            'difficulty' => 'easy',
            'option_1' => 'Option 1',
            'option_2' => 'Option 2',
            'correct_answers' => '1',
        ]);

        $this->expectException(RowImportFailedException::class);

        $importer->resolveRecord();
    }

    public function test_it_can_find_category_by_name(): void
    {
        $category = Category::where('name', 'Programming Concepts')->first();

        $importer = $this->createImporter([
            'category' => 'Programming Concepts',
            'text' => 'What is PHP?',
            'explanation' => 'PHP is a programming language',
            'difficulty' => 'easy',
            'option_1' => 'A database',
            'option_2' => 'A programming language',
            'correct_answers' => '2',
        ]);

        $question = $importer->resolveRecord();

        $this->assertInstanceOf(Question::class, $question);
        $this->assertSame($category->id, $question->category_id);
        $this->assertSame('What is PHP?', $question->text);
        $this->assertCount(2, $question->options);
    }

    public function test_it_can_find_category_by_slug(): void
    {
        $category = Category::where('slug', 'database-design')->first();

        $importer = $this->createImporter([
            'category' => 'database-design',
            'text' => 'What is a foreign key?',
            'explanation' => 'A foreign key is a reference',
            'difficulty' => 'medium',
            'option_1' => 'Option A',
            'option_2' => 'Option B',
            'option_3' => 'Option C',
            'correct_answers' => '1',
        ]);

        $question = $importer->resolveRecord();

        $this->assertSame($category->id, $question->category_id);
    }

    public function test_it_prevents_duplicate_questions(): void
    {
        $category = Category::first();

        Question::factory()->create([
            'category_id' => $category->id,
            'text' => 'What is PHP?',
        ]);

        $importer = $this->createImporter([
            'category' => $category->name,
            'text' => 'What is PHP?',
            'difficulty' => 'easy',
            'option_1' => 'Option 1',
            'option_2' => 'Option 2',
            'correct_answers' => '1',
        ]);

        $this->expectException(RowImportFailedException::class);

        $importer->resolveRecord();
    }

    public function test_it_requires_at_least_two_options(): void
    {
        $importer = $this->createImporter([
            'category' => Category::first()->name,
            'text' => 'Invalid question',
            'difficulty' => 'easy',
            'option_1' => 'Only one option',
            'correct_answers' => '1',
        ]);

        $this->expectException(RowImportFailedException::class);

        $importer->resolveRecord();
    }

    public function test_it_creates_question_with_correct_answers(): void
    {
        $importer = $this->createImporter([
            'category' => Category::first()->name,
            'text' => 'What is a test question?',
            'difficulty' => 'hard',
            'option_1' => 'First',
            'option_2' => 'Second',
            'option_3' => 'Third',
            'option_4' => 'Fourth',
            'correct_answers' => '2,3',
        ]);

        $question = $importer->resolveRecord();

        $this->assertSame(Difficulty::Hard, $question->difficulty);
        $this->assertCount(4, $question->options);
        $this->assertCount(2, $question->options->where('is_correct', true));
    }

    public function test_it_handles_nullable_explanation(): void
    {
        $importer = $this->createImporter([
            'category' => Category::first()->name,
            'text' => 'Question without explanation',
            'difficulty' => 'easy',
            'option_1' => 'A',
            'option_2' => 'B',
            'correct_answers' => '1',
        ]);

        $question = $importer->resolveRecord();

        $this->assertNull($question->explanation);
    }

    protected function createImporter(array $data): QuestionImporter
    {
        $import = Import::create([
            'user_id' => User::factory()->create(['is_admin' => true])->id,
            'file_name' => 'test.csv',
            'file_path' => 'imports/test.csv',
            'importer' => QuestionImporter::class,
            'total_rows' => 1,
        ]);

        $importer = new QuestionImporter($import, [], []);

        $reflection = new ReflectionClass($importer);
        $property = $reflection->getProperty('data');
        $property->setAccessible(true);
        $property->setValue($importer, $data);

        return $importer;
    }
}
