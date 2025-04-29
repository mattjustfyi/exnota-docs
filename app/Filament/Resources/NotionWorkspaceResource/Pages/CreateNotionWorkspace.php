<?php

namespace App\Filament\Resources\NotionWorkspaceResource\Pages;

use App\Filament\Resources\NotionWorkspaceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateNotionWorkspace extends CreateRecord
{
    protected static string $resource = NotionWorkspaceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
