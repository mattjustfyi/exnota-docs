<?php

namespace App\Filament\Resources\NotionDatabaseResource\Pages;

use App\Filament\Resources\NotionDatabaseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNotionDatabases extends ListRecords
{
    protected static string $resource = NotionDatabaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
