<?php

namespace App\Filament\Resources\NotionDatabaseResource\Pages;

use App\Filament\Resources\NotionDatabaseResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateNotionDatabase extends CreateRecord
{
    protected static string $resource = NotionDatabaseResource::class;
}
