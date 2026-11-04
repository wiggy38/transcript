<?php

namespace App\Filament\Resources\Transcriptions\RelationManagers;

use App\Enums\EtapeTraitement;
use App\Support\Duree;
use App\Support\Montant;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/** Durée, résultat et coût de chaque étape du traitement (journal processing_logs). */
class ProcessingLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'processingLogs';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('administration.traitements.etapes');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('etape')
                    ->label(__('administration.traitements.etape'))
                    ->formatStateUsing(fn (EtapeTraitement $state): string => __('administration.etapes.'.$state->value)),
                TextColumn::make('duree_ms')
                    ->label(__('administration.traitements.duree'))
                    ->placeholder('—')
                    ->formatStateUsing(fn (?int $state): ?string => $state !== null ? Duree::formater(intdiv($state, 1000)) : null),
                IconColumn::make('succes')
                    ->label(__('administration.traitements.resultat'))
                    ->boolean(),
                TextColumn::make('message')
                    ->label(__('administration.traitements.message'))
                    ->placeholder('—')
                    ->wrap(),
                TextColumn::make('cout_usd')
                    ->label(__('administration.traitements.cout_etape'))
                    ->placeholder('—')
                    ->formatStateUsing(fn ($state): ?string => $state !== null ? Montant::dollars((float) $state) : null),
            ])
            ->paginated(false);
    }
}
