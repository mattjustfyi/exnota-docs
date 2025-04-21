<?php

namespace App\Filament\Resources\NotionWorkspaceResource\Pages;

use App\Filament\Resources\NotionWorkspaceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNotionWorkspaces extends ListRecords
{
    protected static string $resource = NotionWorkspaceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
