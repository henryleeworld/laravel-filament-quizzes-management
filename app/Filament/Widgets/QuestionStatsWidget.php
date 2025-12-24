<?php

namespace App\Filament\Widgets;

use App\Models\Question;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class QuestionStatsWidget extends TableWidget
{
    protected static ?string $heading = 'Question effectiveness';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 3;

    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    protected function getTableHeading(): string
    {
        return __(static::$heading);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn (): Builder => Question::query()
                    ->with('category')
                    ->withCount([
                        'attemptAnswers as times_answered' => fn (Builder $query) => $query->whereNotNull('is_correct'),
                        'attemptAnswers as correct_count' => fn (Builder $query) => $query->where('is_correct', true),
                    ])
            )
            ->columns([
                TextColumn::make('text')
                    ->label(__('Question'))
                    ->limit(50)
                    ->tooltip(fn (Question $record): string => $record->text)
                    ->searchable(),
                TextColumn::make('category.name')
                    ->label(__('Category'))
                    ->sortable(),
                TextColumn::make('difficulty')
                    ->label(__('Difficulty'))
                    ->badge(),
                TextColumn::make('times_answered')
                    ->label(__('Times answered'))
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('percent_correct')
                    ->label(__('% correct'))
                    ->getStateUsing(function (Question $record): string {
                        if ($record->times_answered === 0) {
                            return '-';
                        }

                        $percent = ($record->correct_count / $record->times_answered) * 100;

                        return number_format($percent, 0).'%';
                    })
                    ->color(function (Question $record): ?string {
                        if ($record->times_answered === 0) {
                            return null;
                        }

                        $percent = ($record->correct_count / $record->times_answered) * 100;

                        return match (true) {
                            $percent >= 70 => 'success',
                            $percent >= 40 => 'warning',
                            default => 'danger',
                        };
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderByRaw(
                            'CASE WHEN times_answered = 0 THEN NULL ELSE (correct_count * 100.0 / times_answered) END '.$direction
                        );
                    })
                    ->alignCenter(),
            ])
            ->defaultSort('times_answered', 'desc')
            ->paginated([10]);
    }
}
