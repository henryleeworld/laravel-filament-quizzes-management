<?php

namespace App\Filament\Resources\Questions\Tables;

use App\Enums\Difficulty;
use App\Models\Category;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class QuestionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('text')
                    ->label(__('Question'))
                    ->formatStateUsing(fn (string $state): string => __($state))
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->wrap(),
                TextColumn::make('category.name')
                    ->label(__('Category'))
                    ->formatStateUsing(fn (string $state): string => __($state))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('difficulty')
                    ->label(__('Difficulty'))
                    ->badge()
                    ->color(fn (Difficulty $state): string => match ($state) {
                        Difficulty::Easy => 'success',
                        Difficulty::Medium => 'warning',
                        Difficulty::Hard => 'danger',
                    })
                    ->sortable(),
                ImageColumn::make('image_path')
                    ->label(__('Image'))
                    ->visibility('private')
                    ->toggleable(),
                TextColumn::make('options_count')
                    ->label(__('Options'))
                    ->counts('options')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('Created at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label(__('Category'))
                    ->relationship('category', 'name')
                    ->getOptionLabelFromRecordUsing(fn (Category $record) => __($record->name))
                    ->multiple()
                    ->preload(),
                SelectFilter::make('difficulty')
                    ->label(__('Difficulty'))
                    ->options([
                        Difficulty::Easy->value => __('Easy'),
                        Difficulty::Hard->value => __('Hard'),
                        Difficulty::Medium->value => __('Medium'),
                    ])
                    ->multiple(),
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
