<?php

namespace App\Filament\Widgets;

use App\Services\Administration\IndicateursAdministration;
use App\Support\Montant;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** Chiffres du tableau de bord (cahier §5). Fenêtres dans config/voxia.php, « administration.indicateurs ». */
class IndicateursWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    // Chiffres rendus avec la page, sans seconde requête.
    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $indicateurs = app(IndicateursAdministration::class);
        $heures = (int) config('voxia.administration.indicateurs.fenetre_echecs_heures');
        $jours = (int) config('voxia.administration.indicateurs.fenetre_jours');
        $seuil = (float) config('voxia.administration.alerte_echecs.seuil');

        $echecs = $indicateurs->tauxEchecTelechargement(now()->subHours($heures));
        $coutHeure = $indicateurs->coutMoyenParHeureVideo(now()->subDays($jours));

        return [
            Stat::make(__('administration.tableau.traitements_jour'), $indicateurs->traitementsDuJour()),

            Stat::make(__('administration.tableau.echecs', ['heures' => $heures]), $echecs['taux'] === null
                    ? '—'
                    : number_format($echecs['taux'] * 100, 1, ',', ' ').' %')
                ->description($echecs['tentatives'] > 0
                    ? __('administration.tableau.echecs_detail', ['echecs' => $echecs['echecs'], 'tentatives' => $echecs['tentatives']])
                    : __('administration.tableau.echecs_aucun'))
                ->color($echecs['taux'] !== null && $echecs['taux'] > $seuil ? 'danger' : 'success'),

            Stat::make(__('administration.tableau.cout_heure', ['jours' => $jours]), $coutHeure === null
                ? '—'
                : Montant::dollars($coutHeure)
            )->description($coutHeure === null ? __('administration.tableau.cout_heure_aucun') : null),

            Stat::make(__('administration.tableau.inscriptions', ['jours' => $jours]), $indicateurs->inscriptions(now()->subDays($jours))),

            Stat::make(__('administration.tableau.conversions', ['jours' => $jours]), $indicateurs->conversions(now()->subDays($jours)))
                ->description(__('administration.tableau.conversions_detail')),
        ];
    }
}
