<?php

namespace App\Services\Administration;

use App\Enums\ActionAdministration;
use App\Enums\CibleAjustement;
use App\Enums\Plan;
use App\Exceptions\ActionAdministrationRefusee;
use App\Models\JournalAdministration;
use App\Models\PackAchat;
use App\Models\UsageRecord;
use App\Models\User;
use App\Services\Plans\PlanFeatures;
use App\Services\Quota\QuotaService;
use Illuminate\Support\Facades\DB;

/**
 * Ajuste un quota depuis le back-office. Un ajout de quota est une ligne de consommation négative,
 * écrite dans le même journal que les consommations réelles. L'action est aussi inscrite au
 * journal d'administration, avec le motif saisi (obligatoire).
 */
class AjustementQuota
{
    public function __construct(
        private readonly PlanFeatures $droits,
        private readonly QuotaService $quotas,
    ) {}

    /**
     * Compteurs ajustables pour ce compte : ceux que son plan porte, et les Packs encore valables.
     *
     * @return list<CibleAjustement>
     */
    public function ciblesPour(User $utilisateur): array
    {
        $plan = $this->droits->planDe($utilisateur);
        $cibles = [];

        if ($plan === Plan::Free) {
            $cibles[] = CibleAjustement::Videos;
        }

        if ($this->droits->secondesMensuelles($plan) !== null) {
            $cibles[] = CibleAjustement::HeuresMois;
        }

        if ($this->droits->creditsIaMensuels($plan) > 0) {
            $cibles[] = CibleAjustement::CreditsIa;
        }

        if ($this->packValide($utilisateur) !== null) {
            $cibles[] = CibleAjustement::HeuresPack;
        }

        return $cibles;
    }

    /**
     * @param  int  $quantite  Positif : ajoute au quota ; négatif : retire. En heures pour les compteurs « heures ».
     *
     * @throws ActionAdministrationRefusee
     */
    public function ajuster(User $utilisateur, User $administrateur, CibleAjustement $compteur, int $quantite, string $motif): UsageRecord
    {
        $motif = trim($motif);

        if ($motif === '') {
            throw new ActionAdministrationRefusee(ActionAdministrationRefusee::MOTIF_VIDE);
        }

        if ($quantite === 0) {
            throw new ActionAdministrationRefusee(ActionAdministrationRefusee::QUANTITE_NULLE);
        }

        return DB::transaction(function () use ($utilisateur, $administrateur, $compteur, $quantite, $motif) {
            $utilisateur = User::query()->whereKey($utilisateur->getKey())->lockForUpdate()->firstOrFail();

            if (! in_array($compteur, $this->ciblesPour($utilisateur), true)) {
                throw new ActionAdministrationRefusee(ActionAdministrationRefusee::CIBLE_INDISPONIBLE);
            }

            $plan = $this->droits->planDe($utilisateur);
            $enHeures = in_array($compteur, [CibleAjustement::HeuresMois, CibleAjustement::HeuresPack], true);
            $valeur = $enHeures ? $quantite * PlanFeatures::SECONDES_PAR_HEURE : $quantite;

            // Pack : la ligne pointe vers l'achat, sans période ni plan du compte.
            $pack = $compteur === CibleAjustement::HeuresPack ? $this->packValide($utilisateur) : null;

            $ligne = UsageRecord::create([
                'user_id' => $utilisateur->getKey(),
                'unite' => $compteur->unite(),
                'plan' => $pack !== null ? Plan::Pack : ($compteur === CibleAjustement::Videos ? Plan::Free : $plan),
                'periode' => $pack !== null ? null : $this->periode($utilisateur, $compteur === CibleAjustement::Videos ? Plan::Free : $plan),
                'pack_achat_id' => $pack?->getKey(),
                'quantite' => -$valeur,
                'motif' => 'ajustement_admin',
            ]);

            JournalAdministration::create([
                'administrateur_id' => $administrateur->getKey(),
                'user_id' => $utilisateur->getKey(),
                'action' => ActionAdministration::AjustementQuota,
                'motif' => $motif,
                'details' => [
                    'compteur' => $compteur->value,
                    'quantite' => $quantite,
                    'usage_record_id' => $ligne->getKey(),
                ],
            ]);

            return $ligne;
        });
    }

    /** Le Pack valable qui se consomme en premier (le plus ancien), ou null. */
    private function packValide(User $utilisateur): ?PackAchat
    {
        return PackAchat::query()
            ->where('user_id', $utilisateur->getKey())
            ->where('expire_at', '>', now())
            ->orderBy('expire_at')
            ->orderBy('id')
            ->first();
    }

    private function periode(User $utilisateur, Plan $plan): string
    {
        return $this->quotas->periode($utilisateur, $plan);
    }
}
