<?php

namespace App\Filament\Resources\NotionDatabaseResource\Pages;

use App\Filament\Resources\NotionDatabaseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNotionDatabase extends EditRecord
{
    protected static string $resource = NotionDatabaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
