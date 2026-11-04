<?php

namespace App\Services\Administration;

use App\Mail\AlerteEchecsTelechargement as CourrielAlerte;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Alerte les administrateurs quand le taux d'échec du téléchargement dépasse le seuil sur la
 * fenêtre récente. Une alerte n'est pas renvoyée avant le délai configuré (une seule par délai).
 */
class AlerteEchecsTelechargement
{
    public const CLE_DELAI = 'administration:alerte_echecs_telechargement';

    public function __construct(private readonly IndicateursAdministration $indicateurs) {}

    /** @return bool  vrai si une alerte a été mise en file */
    public function verifier(?CarbonInterface $maintenant = null): bool
    {
        $maintenant ??= now();
        $fenetre = (int) config('voxia.administration.alerte_echecs.fenetre_minutes');
        $seuil = (float) config('voxia.administration.alerte_echecs.seuil');
        $delai = (int) config('voxia.administration.alerte_echecs.delai_minutes');

        $mesure = $this->indicateurs->tauxEchecTelechargement($maintenant->copy()->subMinutes($fenetre));

        if ($mesure['taux'] === null || $mesure['taux'] <= $seuil) {
            return false;
        }

        $administrateurs = User::query()->where('is_admin', true)->get();

        if ($administrateurs->isEmpty()) {
            Log::warning('Alerte d\'échecs de téléchargement non envoyée : aucun administrateur. Voir voxia:make-admin.');

            return false;
        }

        if ($delai > 0 && ! Cache::add(self::CLE_DELAI, $maintenant->toIso8601String(), $maintenant->copy()->addMinutes($delai))) {
            return false;
        }

        foreach ($administrateurs as $administrateur) {
            Mail::to($administrateur)->queue(new CourrielAlerte(
                administrateur: $administrateur,
                taux: (float) $mesure['taux'],
                tentatives: $mesure['tentatives'],
                echecs: $mesure['echecs'],
                seuil: $seuil,
                fenetreMinutes: $fenetre,
            ));
        }

        return true;
    }
}
