<?php

namespace App\Services\Administration;

use App\Enums\Plan;
use App\Models\PackAchat;
use App\Models\User;
use App\Services\Plans\PlanFeatures;
use App\Services\Quota\QuotaService;
use App\Support\Duree;

/**
 * Consommation du mois d'un compte, en lignes lisibles pour la liste des utilisateurs.
 * S'appuie sur QuotaService::resume, donc sur les mêmes règles que l'application.
 */
class ConsommationUtilisateur
{
    public function __construct(
        private readonly QuotaService $quotas,
        private readonly PlanFeatures $droits,
    ) {}

    /** @return list<string> */
    public function lignes(User $utilisateur): array
    {
        $resume = $this->quotas->resume($utilisateur);
        $lignes = [];

        // Un ajout par le back-office est une consommation négative : la limite affichée l'inclut.
        if ($resume['plan'] === Plan::Free) {
            [$utilisees, $limite] = $this->effectif((int) $resume['videos_utilisees'], (int) $resume['videos_limite']);
            $lignes[] = __('administration.consommation.videos', ['utilisees' => $utilisees, 'limite' => $limite]);
        }

        $volume = $this->droits->secondesMensuelles($resume['plan']);

        if ($volume !== null) {
            [$utilisees, $limite] = $this->effectif($volume - (int) $resume['secondes_mois'], $volume);
            $lignes[] = __('administration.consommation.heures', [
                'utilisees' => Duree::formater($utilisees),
                'limite' => Duree::formater($limite),
            ]);
        }

        if (($resume['credits_ia_limite'] ?? 0) > 0) {
            [$utilisees, $limite] = $this->effectif($resume['credits_ia_limite'] - (int) $resume['credits_ia'], $resume['credits_ia_limite']);
            $lignes[] = __('administration.consommation.credits', ['utilisees' => $utilisees, 'limite' => $limite]);
        }

        // Le résumé ne donne les Packs que pour les plans payants : on les relit ici pour tous les comptes.
        $packs = PackAchat::query()
            ->where('user_id', $utilisateur->getKey())
            ->where('expire_at', '>', now())
            ->withSum('usageRecords as consommees', 'quantite')
            ->get();

        if ($packs->isNotEmpty()) {
            $restant = $packs->sum(fn (PackAchat $pack) => max(0, $pack->secondes - (int) $pack->consommees));

            $lignes[] = trans_choice('administration.consommation.packs', $packs->count(), [
                'nombre' => $packs->count(),
                'restant' => Duree::formater($restant),
            ]);
        }

        return $lignes ?: [__('administration.consommation.aucune')];
    }

    /**
     * Consommation réelle et limite effective. Une consommation négative (ajout accordé) porte la
     * limite à la hausse et ramène la consommation affichée à zéro.
     *
     * @return array{0: int, 1: int}
     */
    private function effectif(int $consommee, int $limite): array
    {
        return [max(0, $consommee), $limite + max(0, -$consommee)];
    }
}
