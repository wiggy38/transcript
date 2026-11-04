<?php

namespace App\Filament\Actions;

use App\Enums\CibleAjustement;
use App\Exceptions\ActionAdministrationRefusee;
use App\Models\User;
use App\Services\Administration\AjustementQuota as Service;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

/** Ajuste un compteur de quota d'un compte, avec motif obligatoire (journalisé). */
class AjusterQuota extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'ajuster';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('administration.utilisateurs.action'))
            ->icon('heroicon-o-adjustments-horizontal')
            ->modalHeading(__('administration.utilisateurs.action_titre'))
            ->schema([
                Select::make('compteur')
                    ->label(__('administration.utilisateurs.compteur'))
                    ->options(fn (User $record): array => collect(app(Service::class)->ciblesPour($record))
                        ->mapWithKeys(fn (CibleAjustement $cible) => [$cible->value => __('administration.compteurs.'.$cible->value)])
                        ->all())
                    ->required(),
                TextInput::make('quantite')
                    ->label(__('administration.utilisateurs.quantite'))
                    ->helperText(__('administration.utilisateurs.quantite_aide'))
                    ->integer()
                    ->required()
                    ->notIn([0]),
                Textarea::make('motif')
                    ->label(__('administration.utilisateurs.motif'))
                    ->required()
                    ->maxLength(255),
            ])
            ->action(function (array $data, User $record): void {
                try {
                    app(Service::class)->ajuster(
                        $record,
                        auth()->user(),
                        CibleAjustement::from((string) $data['compteur']),
                        (int) $data['quantite'],
                        (string) $data['motif'],
                    );

                    Notification::make()
                        ->title(__('administration.utilisateurs.succes'))
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
