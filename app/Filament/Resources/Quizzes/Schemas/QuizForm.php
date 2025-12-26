<?php

namespace App\Filament\Resources\Quizzes\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class QuizForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label(__('Title'))
                    ->columnSpanFull()
                    ->maxLength(200)
                    ->required(),
                Textarea::make('description')
                    ->label(__('Description'))
                    ->nullable()
                    ->rows(3)
                    ->columnSpanFull(),
                TextInput::make('question_count')
                    ->label(__('Number of questions'))
                    ->numeric()
                    ->minValue(1)
                    ->default(10)
                    ->required(),
                TextInput::make('time_limit_minutes')
                    ->label(__('Time limit'))
                    ->numeric()
                    ->minValue(1)
                    ->suffix('minutes')
                    ->nullable()
                    ->helperText(__('Leave empty for no time limit')),
                Toggle::make('shuffle_questions')
                    ->label(__('Shuffle questions'))
                    ->default(false)
                    ->helperText(__('Randomize question order for each attempt')),
                Toggle::make('shuffle_answers')
                    ->label(__('Shuffle answers'))
                    ->default(false)
                    ->helperText(__('Randomize answer options for each question')),
                Toggle::make('allow_multiple_attempts')
                    ->label(__('Allow multiple attempts'))
                    ->default(true)
                    ->helperText(__('Allow students to retake this quiz')),
                CheckboxList::make('questions')
                    ->label(__('Select questions'))
                    ->relationship('questions', 'text')
                    ->searchable()
                    ->bulkToggleable()
                    ->columns(1)
                    ->columnSpanFull()
                    ->minItems(1)
                    ->rules([
                        function (Get $get) {
                            return function (string $attribute, $value, $fail) use ($get) {
                                $questionCount = $get('question_count');

                                if (! is_array($value)) {
                                    return;
                                }

                                $selectedCount = count($value);

                                if ($questionCount && $selectedCount < $questionCount) {
                                    $fail(__('You must select at least :question_count questions to match the question count setting.', ['question_count' => $questionCount]));
                                }
                            };
                        },
                    ])
                    ->helperText(__('Select questions to include in this quiz'))
                    ->required(),
            ]);
    }
}
