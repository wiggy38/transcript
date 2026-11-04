<?php

namespace App\Filament\Resources\JournalAdministrations;

use App\Filament\Resources\JournalAdministrations\Pages\ListJournalAdministrations;
use App\Filament\Resources\JournalAdministrations\Tables\JournalAdministrationsTable;
use App\Models\JournalAdministration;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/** Journal des actions du back-office. Lecture seule : une trace ne se modifie pas. */
class JournalAdministrationResource extends Resource
{
    protected static ?string $model = JournalAdministration::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('administration.navigation.journal');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('administration.navigation.groupe');
    }

    public static function getModelLabel(): string
    {
        return __('administration.journal.titre');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return JournalAdministrationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJournalAdministrations::route('/'),
        ];
    }
}
