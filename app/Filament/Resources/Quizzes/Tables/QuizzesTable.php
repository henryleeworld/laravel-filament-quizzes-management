<?php

namespace App\Filament\Resources\Quizzes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class QuizzesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('Title'))
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('question_count')
                    ->label(__('Questions'))
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('time_limit_minutes')
                    ->label(__('Time limit'))
                    ->suffix(' ' . __('min'))
                    ->sortable()
                    ->alignCenter()
                    ->placeholder(__('No limit')),
                IconColumn::make('shuffle_questions')
                    ->label(__('Shuffle questions'))
                    ->boolean()
                    ->alignCenter()
                    ->toggleable(),
                IconColumn::make('shuffle_answers')
                    ->label(__('Shuffle answers'))
                    ->boolean()
                    ->alignCenter()
                    ->toggleable(),
                IconColumn::make('allow_multiple_attempts')
                    ->label(__('Retakes'))
                    ->boolean()
                    ->alignCenter()
                    ->toggleable(),
                TextColumn::make('attempts_count')
                    ->label(__('Attempts'))
                    ->counts('attempts')
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('created_at')
                    ->label(__('Created at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
