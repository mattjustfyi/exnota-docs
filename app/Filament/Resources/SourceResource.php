<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SourceResource\Pages;
use App\Models\Source;
use App\Models\NotionDatabase;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SourceResource extends Resource
{
    protected static ?string $model = Source::class;

    protected static ?string $recordTitleAttribute = 'path';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Textarea::make('path')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Select::make('repository_id')
                    ->relationship('repository', 'url')
                    ->required(),
                Forms\Components\Select::make('notion_database_id')
                    ->label('Notion Database')
                    ->options(
                        NotionDatabase::with('notionWorkspace')
                            ->get()
                            ->mapWithKeys(function ($database) {
                                return [
                                    $database->id => "{$database->name} ({$database->notionWorkspace->name})"
                                ];
                            })
                    )
                    ->searchable()
                    ->preload(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('path')
                    ->limit(30),
                Tables\Columns\TextColumn::make('repository.url')
                    ->limit(30)
                    ->sortable(),
                Tables\Columns\TextColumn::make('notionDatabase.name')
                    ->limit(30)
                    ->sortable(),
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
            'index' => Pages\ListSources::route('/'),
            'create' => Pages\CreateSource::route('/create'),
            'edit' => Pages\EditSource::route('/{record}/edit'),
        ];
    }
}
