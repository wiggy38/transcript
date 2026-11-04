<?php

namespace App\Filament\Resources\Transcriptions;

use App\Filament\Resources\Transcriptions\Pages\ListTranscriptions;
use App\Filament\Resources\Transcriptions\Pages\ViewTranscription;
use App\Filament\Resources\Transcriptions\RelationManagers\ProcessingLogsRelationManager;
use App\Filament\Resources\Transcriptions\Schemas\TranscriptionInfolist;
use App\Filament\Resources\Transcriptions\Tables\TranscriptionsTable;
use App\Models\Transcription;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TranscriptionResource extends Resource
{
    protected static ?string $model = Transcription::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('administration.navigation.traitements');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('administration.navigation.groupe');
    }

    public static function getModelLabel(): string
    {
        return __('administration.traitements.titre');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return TranscriptionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TranscriptionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ProcessingLogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTranscriptions::route('/'),
            'view' => ViewTranscription::route('/{record}'),
        ];
    }
}
