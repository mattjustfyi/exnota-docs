<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotionDatabaseResource\Pages;
use App\Models\NotionDatabase;
use App\Models\NotionWorkspace;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class NotionDatabaseResource extends Resource
{
    protected static ?string $model = NotionDatabase::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('id')
                    ->required()
                    ->disabled()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('notion_id')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('notion_home_id')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('notion_workspace_id')
                    ->label('Notion Workspace')
                    ->options(NotionWorkspace::pluck('name', 'id'))
                    ->required()
                    ->searchable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('notion_id')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('notionWorkspace.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotionDatabases::route('/'),
            'create' => Pages\CreateNotionDatabase::route('/create'),
            'edit' => Pages\EditNotionDatabase::route('/{record}/edit'),
        ];
    }
}
