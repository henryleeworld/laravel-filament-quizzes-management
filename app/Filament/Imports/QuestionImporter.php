<?php

namespace App\Filament\Imports;

use App\Enums\Difficulty;
use App\Models\Category;
use App\Models\Question;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class QuestionImporter extends Importer
{
    protected static ?string $model = Question::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('category')
                ->label(__('Category (name or slug)'))
                ->requiredMapping()
                ->rules(['required'])
                ->exampleHeader(__('Category'))
                ->example(__('Programming concepts')),
            ImportColumn::make('text')
                ->label(__('Question text'))
                ->requiredMapping()
                ->rules(['required', 'string', 'max:1000'])
                ->exampleHeader(__('Question'))
                ->example(__('What is the difference between == and === in JavaScript?')),
            ImportColumn::make('explanation')
                ->label(__('Explanation'))
                ->rules(['nullable', 'string', 'max:2000'])
                ->exampleHeader(__('Explanation'))
                ->example(__('The == operator compares values with type coercion, while === compares both value and type without coercion.')),
            ImportColumn::make('difficulty')
                ->label(__('Difficulty'))
                ->requiredMapping()
                ->rules(['required', 'in:easy,medium,hard'])
                ->exampleHeader(__('Difficulty'))
                ->example('medium'),
            ImportColumn::make('option_1')
                ->label(__('Option 1'))
                ->requiredMapping()
                ->rules(['required', 'string', 'max:500'])
                ->exampleHeader(__('Option 1'))
                ->example(__('They are exactly the same')),
            ImportColumn::make('option_2')
                ->label(__('Option 2'))
                ->requiredMapping()
                ->rules(['required', 'string', 'max:500'])
                ->exampleHeader(__('Option 2'))
                ->example(__('== checks value only, === checks value and type')),
            ImportColumn::make('option_3')
                ->label(__('Option 3'))
                ->rules(['nullable', 'string', 'max:500'])
                ->exampleHeader(__('Option 3'))
                ->example(__('=== is deprecated')),
            ImportColumn::make('option_4')
                ->label(__('Option 4'))
                ->rules(['nullable', 'string', 'max:500'])
                ->exampleHeader(__('Option 4'))
                ->example(__('== is faster than ===')),
            ImportColumn::make('option_5')
                ->label(__('Option 5'))
                ->rules(['nullable', 'string', 'max:500'])
                ->exampleHeader(__('Option 5'))
                ->example(''),
            ImportColumn::make('correct_answers')
                ->label(__('Correct answer(s)'))
                ->requiredMapping()
                ->rules(['required', 'regex:/^[1-5](,[1-5])*$/'])
                ->exampleHeader(__('Correct answers'))
                ->example('2'),
        ];
    }

    public function resolveRecord(): ?Question
    {
        $category = Category::where('name', $this->data['category'])
            ->orWhere('slug', $this->data['category'])
            ->first();

        if (! $category) {
            throw new RowImportFailedException(__('Category :category not found.', ['category' =>$this->data['category']]));
        }

        $existingQuestion = Question::where('text', $this->data['text'])
            ->where('category_id', $category->id)
            ->first();

        if ($existingQuestion) {
            throw new RowImportFailedException(__('Question already exists in this category.'));
        }

        $options = array_filter([
            $this->data['option_1'] ?? null,
            $this->data['option_2'] ?? null,
            $this->data['option_3'] ?? null,
            $this->data['option_4'] ?? null,
            $this->data['option_5'] ?? null,
        ]);

        if (count($options) < 2) {
            throw new RowImportFailedException(__('At least 2 options are required.'));
        }

        $correctAnswers = array_map('intval', explode(',', $this->data['correct_answers']));

        foreach ($correctAnswers as $answerIndex) {
            if ($answerIndex < 1 || $answerIndex > count($options)) {
                throw new RowImportFailedException(__('Correct answer index :answer_index is out of range.', ['answer_index' => $answerIndex]));
            }
        }

        $question = Question::create([
            'category_id' => $category->id,
            'text' => $this->data['text'],
            'explanation' => $this->data['explanation'] ?? null,
            'difficulty' => Difficulty::from($this->data['difficulty']),
        ]);

        foreach ($options as $index => $optionText) {
            $question->options()->create([
                'text' => $optionText,
                'is_correct' => in_array($index + 1, $correctAnswers),
                'order' => $index + 1,
            ]);
        }

        return $question;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = __('Your question import has completed and ') . Number::format($import->successful_rows) . ' ' . __(str('row')->plural($import->successful_rows)) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '. Number::format($failedRowsCount) . ' ' . __(str('row')->plural($failedRowsCount)). __(' failed to import.');
        }

        return $body;
    }
}
