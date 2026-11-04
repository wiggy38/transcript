<?php

namespace App\Services\Plans;

use App\Enums\FormatExport;
use App\Enums\Plan;
use App\Support\CheminLocal;
use App\Support\Montant;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

/**
 * Cartes des plans présentées dans les fenêtres d'upgrade (reprise de la section Tarifs de la maquette).
 * Prix, volumes et durées viennent de config/voxia.php ; les phrases viennent de lang/fr/upgrade.php et lang/en/upgrade.php.
 * Les liens mènent à la page de paiement, avec l'adresse de retour de l'écran d'origine.
 */
class PresentationTarifs
{
    public function __construct(private readonly PlanFeatures $droits) {}

    /**
     * @param  list<Plan|string>  $plans  plans à présenter, dans l'ordre : free, pro, pack
     * @param  string  $retour  écran d'origine (adresse absolue ou chemin)
     * @param  ?int  $transcriptionId  aperçu partiel à relancer après paiement (voir PaiementController)
     * @return list<array{cle: string, nom: string, prix: string, periode: ?string, annuel: ?string, vedette: bool, actuel: bool, lignes: list<string>, actions: list<array{libelle: string, url: string}>}>
     */
    public function cartes(array $plans, string $retour, ?int $transcriptionId = null): array
    {
        return array_map(function (Plan|string $plan) use ($retour, $transcriptionId) {
            $cle = $plan instanceof Plan ? $plan->value : $plan;

            return match ($cle) {
                'free' => $this->gratuit(),
                'pro' => $this->pro($retour, $transcriptionId),
                'pack' => $this->pack($retour, $transcriptionId),
                default => throw new InvalidArgumentException("Plan non présenté : {$cle}"),
            };
        }, $plans);
    }

    /** @return array<string, mixed> */
    private function gratuit(): array
    {
        $formats = $this->formats(Plan::Free);

        return $this->carte('free', Plan::Free, [
            'prix' => Montant::dollars(0),
            'vedette' => false,
            'lignes' => [
                __('upgrade.lignes.videos', ['nombre' => $this->droits->videosMensuelles(Plan::Free)]),
                __('upgrade.lignes.duree_minutes', ['minutes' => intdiv($this->droits->dureeMaxSecondes(Plan::Free), 60)]),
                __('upgrade.lignes.exports', ['formats' => $formats]),
            ],
        ]);
    }

    /** @return array<string, mixed> */
    private function pro(string $retour, ?int $transcriptionId): array
    {
        $mensuel = (float) config('voxia.plans.pro.price_monthly_usd');
        $annuel = (float) config('voxia.plans.pro.price_yearly_usd');

        return $this->carte('pro', Plan::Pro, [
            'prix' => Montant::dollars($mensuel),
            'periode' => __('upgrade.periode.mois'),
            'annuel' => __('upgrade.annuel', ['prix' => Montant::dollars($annuel), 'mensuel' => Montant::dollars($annuel / 12)]),
            'vedette' => true,
            'lignes' => [
                __('upgrade.lignes.heures_mois', ['heures' => intdiv((int) $this->droits->secondesMensuelles(Plan::Pro), 3600)]),
                __('upgrade.lignes.duree_heures', ['heures' => intdiv($this->droits->dureeMaxSecondes(Plan::Pro), 3600)]),
                __('upgrade.lignes.exports', ['formats' => $this->formats(Plan::Pro)]),
                __('upgrade.lignes.historique'),
                __('upgrade.lignes.ia'),
            ],
            'actions' => [
                ['libelle' => __('upgrade.actions.pro_mensuel', ['prix' => Montant::dollars($mensuel)]), 'url' => $this->lienPaiement('pro_mensuel', $retour, $transcriptionId)],
                ['libelle' => __('upgrade.actions.pro_annuel', ['prix' => Montant::dollars($annuel)]), 'url' => $this->lienPaiement('pro_annuel', $retour, $transcriptionId)],
            ],
        ]);
    }

    /** @return array<string, mixed> */
    private function pack(string $retour, ?int $transcriptionId): array
    {
        $prix = (float) config('voxia.plans.pack.price_usd');

        return $this->carte('pack', Plan::Pack, [
            'prix' => Montant::dollars($prix),
            'periode' => __('upgrade.periode.unique'),
            'vedette' => false,
            'lignes' => [
                __('upgrade.lignes.heures_pack', ['heures' => (int) config('voxia.plans.pack.hours'), 'mois' => $this->droits->validitePackMois()]),
                __('upgrade.lignes.duree_heures', ['heures' => intdiv($this->droits->dureeMaxSecondes(Plan::Pack), 3600)]),
                __('upgrade.lignes.exports', ['formats' => $this->formats(Plan::Pack)]),
                __('upgrade.lignes.historique'),
            ],
            'actions' => [
                ['libelle' => __('upgrade.actions.pack', ['prix' => Montant::dollars($prix)]), 'url' => $this->lienPaiement('pack', $retour, $transcriptionId)],
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $donnees
     * @return array<string, mixed>
     */
    private function carte(string $cle, Plan $plan, array $donnees): array
    {
        return array_merge([
            'cle' => $cle,
            'nom' => $this->droits->libellePlan($plan),
            'periode' => null,
            'annuel' => null,
            'actions' => [],
            'actuel' => Auth::check() && $this->droits->planDe(Auth::user()) === $plan,
        ], $donnees);
    }

    /** Formats d'export du plan, lus dans la config : « TXT, SRT ». */
    private function formats(Plan $plan): string
    {
        $autorises = (array) config("voxia.plans.{$plan->value}.exports", []);

        $libelles = array_map(
            fn (FormatExport $format) => $format->libelle(),
            array_filter(FormatExport::cases(), fn (FormatExport $format) => in_array($format->value, $autorises, true)),
        );

        return implode(', ', $libelles);
    }

    /** Lien vers la page de paiement, avec l'écran d'origine à rejoindre après le paiement (chemin local). */
    private function lienPaiement(string $produit, string $retour, ?int $transcriptionId): string
    {
        $parametres = ['produit' => $produit, 'retour' => CheminLocal::depuisUrl($retour)];

        if ($transcriptionId !== null) {
            $parametres['transcription'] = $transcriptionId;
        }

        return route('paiement.show', $parametres);
    }
}
