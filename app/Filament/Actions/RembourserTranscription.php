<?php

namespace App\Filament\Actions;

use App\Enums\StatutTranscription;
use App\Exceptions\ActionAdministrationRefusee;
use App\Models\Transcription;
use App\Services\Administration\RemboursementTranscription as Service;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;

/** Rembourse un traitement raté (lignes de consommation inverses), avec motif obligatoire. */
class RembourserTranscription extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'rembourser';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('administration.traitements.rembourser'))
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('danger')
            ->modalHeading(__('administration.traitements.rembourser_titre'))
            ->modalDescription(__('administration.traitements.rembourser_description'))
            ->modalSubmitActionLabel(__('administration.traitements.rembourser'))
            ->schema([
                Textarea::make('motif')
                    ->label(__('administration.traitements.motif_remboursement'))
                    ->required()
                    ->maxLength(255),
            ])
            ->visible(fn (Transcription $record): bool => $record->statut === StatutTranscription::Echec
                && $record->usageRecords()->whereNull('annule_usage_record_id')->whereDoesntHave('remboursement')->exists())
            ->action(function (array $data, Transcription $record): void {
                try {
                    $nombre = app(Service::class)->rembourser($record, auth()->user(), (string) $data['motif']);

                    Notification::make()
                        ->title(__('administration.traitements.succes', ['nombre' => $nombre]))
                        ->success()
                        ->send();
                } catch (ActionAdministrationRefusee $erreur) {
                    Notification::make()
                        ->title(__('administration.refus.'.$erreur->raison))
                        ->danger()
                        ->send();
                }
            });
    }
}
