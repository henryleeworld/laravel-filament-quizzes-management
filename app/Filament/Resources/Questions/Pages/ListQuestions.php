<?php

namespace App\Filament\Resources\Questions\Pages;

use App\Filament\Imports\QuestionImporter;
use App\Filament\Resources\Questions\QuestionResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListQuestions extends ListRecords
{
    protected static string $resource = QuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->importer(QuestionImporter::class),
            CreateAction::make(),
        ];
    }
}
