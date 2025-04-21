<?php

namespace App\Filament\Resources\NotionWorkspaceResource\Pages;

use App\Filament\Resources\NotionWorkspaceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNotionWorkspace extends EditRecord
{
    protected static string $resource = NotionWorkspaceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
