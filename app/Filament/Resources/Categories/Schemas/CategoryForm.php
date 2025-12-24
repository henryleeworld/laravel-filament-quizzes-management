<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('Name'))
                    ->maxLength(100)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, callable $set, ?string $operation) {
                        if ($operation === 'create') {
                            $set('slug', Str::slug($state, language: app()->getLocale()));
                        }
                    })
                    ->required(),
                TextInput::make('slug')
                    ->label(__('Slug'))
                    ->maxLength(100)
                    ->unique(ignoreRecord: true)
                    ->rules(['alpha_dash'])
                    ->required(),
            ]);
    }
}
