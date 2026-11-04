<?php

namespace App\Filament\Resources\Transcriptions\Tables;

use App\Enums\MotifEchec;
use App\Enums\SourceTranscription;
use App\Enums\StatutTranscription;
use App\Filament\Actions\RembourserTranscription;
use App\Models\Transcription;
use App\Support\Duree;
use App\Support\Montant;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TranscriptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with('user')
                ->withSum('processingLogs', 'duree_ms'))
            ->columns([
                TextColumn::make('titre')
                    ->label(__('administration.traitements.video'))
                    ->state(fn (Transcription $record): string => $record->titre ?? $record->url_origine ?? '—')
                    ->limit(60)
                    ->searchable(),
                TextColumn::make('user.email')
                    ->label(__('administration.traitements.utilisateur'))
                    ->searchable(),
                TextColumn::make('source')
                    ->label(__('administration.traitements.plateforme'))
                    ->formatStateUsing(fn (SourceTranscription $state): string => __('administration.plateformes.'.$state->value)),
                TextColumn::make('statut')
                    ->label(__('administration.traitements.statut'))
                    ->badge()
                    ->color(fn (StatutTranscription $state): string => match ($state) {
                        StatutTranscription::Termine => 'success',
                        StatutTranscription::Echec => 'danger',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (StatutTranscription $state): string => __('administration.statuts.'.$state->value)),
                TextColumn::make('motif_echec')
                    ->label(__('administration.traitements.motif'))
                    ->placeholder('—')
                    ->formatStateUsing(fn (?MotifEchec $state): ?string => $state ? __('administration.motifs.'.$state->value) : null),
                TextColumn::make('duree_secondes')
                    ->label(__('administration.traitements.duree_video'))
                    ->placeholder('—')
                    ->formatStateUsing(fn (?int $state): ?string => $state !== null ? Duree::formater($state) : null),
                TextColumn::make('processing_logs_sum_duree_ms')
                    ->label(__('administration.traitements.temps'))
                    ->placeholder('—')
                    ->formatStateUsing(fn (?int $state): ?string => $state !== null ? Duree::formater(intdiv($state, 1000)) : null),
                TextColumn::make('cout_estime_usd')
                    ->label(__('administration.traitements.cout'))
                    ->formatStateUsing(fn ($state): string => Montant::dollars((float) $state)),
                TextColumn::make('created_at')
                    ->label(__('administration.traitements.date'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('statut')
                    ->label(__('administration.traitements.statut'))
                    ->options(collect(StatutTranscription::cases())->mapWithKeys(
                        fn (StatutTranscription $statut) => [$statut->value => __('administration.statuts.'.$statut->value)],
                    )->all()),
                Filter::make('periode')
                    ->label(__('administration.traitements.periode'))
                    ->schema([
                        DatePicker::make('du')->label(__('administration.traitements.du')),
                        DatePicker::make('au')->label(__('administration.traitements.au')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['du'] ?? null, fn (Builder $q, $du) => $q->whereDate('created_at', '>=', $du))
                        ->when($data['au'] ?? null, fn (Builder $q, $au) => $q->whereDate('created_at', '<=', $au))),
            ])
            ->recordActions([
                ViewAction::make(),
                RembourserTranscription::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([25, 50, 100]);
    }
}
