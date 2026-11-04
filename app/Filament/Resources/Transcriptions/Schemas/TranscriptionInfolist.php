<?php

namespace App\Filament\Resources\Transcriptions\Schemas;

use App\Enums\MotifEchec;
use App\Enums\SourceTranscription;
use App\Enums\StatutTranscription;
use App\Support\Duree;
use App\Support\Montant;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class TranscriptionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('titre')
                ->label(__('administration.traitements.video'))
                ->placeholder('—'),
            TextEntry::make('user.email')
                ->label(__('administration.traitements.utilisateur')),
            TextEntry::make('url_origine')
                ->label('URL')
                ->placeholder('—'),
            TextEntry::make('source')
                ->label(__('administration.traitements.plateforme'))
                ->formatStateUsing(fn (SourceTranscription $state): string => __('administration.plateformes.'.$state->value)),
            TextEntry::make('statut')
                ->label(__('administration.traitements.statut'))
                ->badge()
                ->formatStateUsing(fn (StatutTranscription $state): string => __('administration.statuts.'.$state->value)),
            TextEntry::make('motif_echec')
                ->label(__('administration.traitements.motif'))
                ->placeholder('—')
                ->formatStateUsing(fn (?MotifEchec $state): ?string => $state ? __('administration.motifs.'.$state->value) : null),
            TextEntry::make('duree_secondes')
                ->label(__('administration.traitements.duree_video'))
                ->placeholder('—')
                ->formatStateUsing(fn (?int $state): ?string => $state !== null ? Duree::formater($state) : null),
            TextEntry::make('cout_estime_usd')
                ->label(__('administration.traitements.cout'))
                ->formatStateUsing(fn ($state): string => Montant::dollars((float) $state)),
            TextEntry::make('created_at')
                ->label(__('administration.traitements.date'))
                ->dateTime(),
        ]);
    }
}
