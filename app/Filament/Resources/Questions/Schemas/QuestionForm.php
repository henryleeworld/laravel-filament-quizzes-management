<?php

namespace App\Filament\Resources\Questions\Schemas;

use App\Enums\Difficulty;
use App\Models\Category;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class QuestionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('category_id')
                    ->label(__('Category'))
                    ->relationship('category', 'name')
                    ->getOptionLabelFromRecordUsing(fn (Category $record) => __($record->name))
                    ->searchable()
                    ->preload()
                    ->required(),
                Textarea::make('text')
                    ->label(__('Question text'))
                    ->rows(3)
                    ->columnSpanFull()
                    ->required(),
                RichEditor::make('explanation')
                    ->label(__('Explanation'))
                    ->nullable()
                    ->columnSpanFull()
                    ->toolbarButtons([
                        'bold',
                        'italic',
                        'bulletList',
                        'orderedList',
                        'link',
                    ]),
                Select::make('difficulty')
                    ->label(__('Difficulty'))
                    ->options(Difficulty::class)
                    ->default(Difficulty::Medium)
                    ->required(),
                FileUpload::make('image_path')
                    ->label(__('Image'))
                    ->image()
                    ->maxSize(2048)
                    ->directory('question-images')
                    ->visibility('private')
                    ->nullable(),
                Repeater::make('options')
                    ->label(__('Options'))
                    ->relationship('options')
                    ->schema([
                        TextInput::make('text')
                            ->label(__('Option text'))
                            ->maxLength(500)
                            ->columnSpanFull()
                            ->required(),
                        Toggle::make('is_correct')
                            ->label(__('Correct answer'))
                            ->default(false),
                    ])
                    ->columns(1)
                    ->reorderable('order')
                    ->orderColumn('order')
                    ->minItems(2)
                    ->maxItems(5)
                    ->defaultItems(2)
                    ->addActionLabel(__('Add option'))
                    ->columnSpanFull()
                    ->rules([
                        function () {
                            return function (string $attribute, $value, $fail) {
                                if (! is_array($value)) {
                                    return;
                                }

                                $hasCorrectAnswer = collect($value)->contains('is_correct', true);

                                if (! $hasCorrectAnswer) {
                                    $fail(__('At least one option must be marked as correct.'));
                                }
                            };
                        },
                    ]),
            ]);
    }
}
