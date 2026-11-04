<?php

namespace App\Filament\Resources\JournalAdministrations\Tables;

use App\Enums\ActionAdministration;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class JournalAdministrationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['administrateur', 'utilisateur']))
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('administration.journal.date'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('administrateur.email')
                    ->label(__('administration.journal.administrateur'))
                    ->placeholder('—'),
                TextColumn::make('utilisateur.email')
                    ->label(__('administration.journal.utilisateur'))
                    ->placeholder('—'),
                TextColumn::make('action')
                    ->label(__('administration.journal.action'))
                    ->badge()
                    ->formatStateUsing(fn (ActionAdministration $state): string => __('administration.actions_journal.'.$state->value)),
                TextColumn::make('motif')
                    ->label(__('administration.journal.motif'))
                    ->limit(80)
                    ->wrap(),
                TextColumn::make('details')
                    ->label(__('administration.journal.details'))
                    ->formatStateUsing(fn (?array $state): string => json_encode($state ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '')
                    ->limit(80),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([25, 50, 100]);
    }
}
