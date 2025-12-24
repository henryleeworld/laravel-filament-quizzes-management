<?php

namespace App\Filament\Widgets;

use App\Models\Attempt;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentAttemptsWidget extends TableWidget
{
    protected static ?string $heading = 'Recent attempts';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

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
                fn (): Builder => Attempt::query()
                    ->whereNotNull('submitted_at')
                    ->with(['user', 'quiz'])
                    ->latest('submitted_at')
            )
            ->columns([
                TextColumn::make('user.name')
                    ->label(__('User'))
                    ->searchable(),
                TextColumn::make('quiz.title')
                    ->label(__('Quiz'))
                    ->limit(30),
                TextColumn::make('score')
                    ->label(__('Score'))
                    ->formatStateUsing(fn ($state): string => number_format($state, 0).'%')
                    ->color(fn ($state): string => $state >= 70 ? 'success' : 'danger'),
                TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->getStateUsing(fn (Attempt $record): string => $record->score >= 70 ? __('Passed') : __('Failed'))
                    ->color(fn (string $state): string => $state === __('Passed') ? 'success' : 'danger'),
                TextColumn::make('submitted_at')
                    ->label(__('Date'))
                    ->dateTime('M j, Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('submitted_at', 'desc')
            ->paginated([10]);
    }
}
