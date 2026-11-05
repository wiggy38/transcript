<?php

namespace App\Livewire;

use App\Enums\FormatExportReecriture;
use App\Enums\FormatReecriture;
use App\Enums\LongueurReecriture;
use App\Enums\Plan;
use App\Enums\StatutSortieIa;
use App\Enums\TonReecriture;
use App\Exceptions\ReecritureRefusee;
use App\Models\AiOutput;
use App\Models\Transcription;
use App\Services\Exports\ExporteurReecriture;
use App\Services\Plans\PlanFeatures;
use App\Services\Reecriture\LancerReecriture;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Livewire\Component;

/**
 * Réécriture d'une transcription en contenu prêt à publier (V2) : article, newsletter, post LinkedIn, thread X.
 *
 * Les formats cochés partent ensemble ; le coût total est affiché avant le lancement. Un format terminé
 * se rouvre sans coût. Régénérer un format coûte 1 crédit et demande une confirmation.
 */
class ReecrireTranscription extends Component
{
    public int $transcriptionId;

    /** Valeurs de FormatReecriture cochées pour le prochain lancement. */
    public array $formats = ['article'];

    public string $ton = 'professionnel';

    public string $longueur = 'moyenne';

    public string $langue = 'fr';

    /** Format affiché dans le panneau (onglet actif). */
    public string $onglet = '';

    /** Motif d'un lancement refusé (voir ReecritureRefusee), ou null. */
    public ?string $refus = null;

    /** Format dont la régénération attend la confirmation, ou null. */
    public ?string $formatAConfirmer = null;

    public function mount(Transcription $transcription): void
    {
        $this->transcriptionId = $transcription->getKey();
        $this->ton = (string) config('voxia.ia.reecriture.ton_defaut');
        $this->longueur = (string) config('voxia.ia.reecriture.longueur_defaut');
        $this->langue = $this->langueParDefaut($transcription);
    }

    public function lancer(LancerReecriture $lancer): void
    {
        $this->refus = null;

        try {
            $sorties = $lancer->lancer(
                Auth::user(),
                $this->transcription(),
                $this->formatsChoisis(),
                TonReecriture::from($this->ton),
                LongueurReecriture::from($this->longueur),
                $this->langue,
            );

            $this->onglet = (string) array_key_first($sorties);
        } catch (ReecritureRefusee $erreur) {
            $this->refus = $erreur->motif;
        }
    }

    public function demanderRegeneration(string $format): void
    {
        $this->refus = null;
        $this->formatAConfirmer = $format;
    }

    public function annulerRegeneration(): void
    {
        $this->formatAConfirmer = null;
    }

    /** Régénérer ne part qu'après confirmation (formatAConfirmer), et décompte 1 crédit. */
    public function regenerer(LancerReecriture $lancer): void
    {
        $this->refus = null;
        $format = FormatReecriture::tryFrom((string) $this->formatAConfirmer);

        if ($format === null) {
            $this->formatAConfirmer = null;

            return;
        }

        try {
            $lancer->lancer(
                Auth::user(),
                $this->transcription(),
                [$format],
                TonReecriture::from($this->ton),
                LongueurReecriture::from($this->longueur),
                $this->langue,
                regenerer: true,
                confirme: true,
            );

            $this->onglet = $format->value;
        } catch (ReecritureRefusee $erreur) {
            $this->refus = $erreur->motif;
        }

        $this->formatAConfirmer = null;
    }

    public function render(PlanFeatures $plans, LancerReecriture $lancer, ExporteurReecriture $exporteur)
    {
        $transcription = $this->transcription();
        $compte = Auth::user();
        $plan = $plans->planDe($compte);

        $sorties = $transcription->aiOutputs()
            ->where('fonctionnalite', 'reecriture')
            ->get()
            ->keyBy('format');

        $onglets = [];

        foreach (FormatReecriture::cases() as $format) {
            if ($sorties->has($format->value)) {
                $onglets[$format->value] = $sorties[$format->value];
            }
        }

        $ongletActif = array_key_exists($this->onglet, $onglets) ? $this->onglet : (array_key_first($onglets) ?? '');
        $sortieActive = $onglets[$ongletActif] ?? null;
        $contenu = $sortieActive?->statut === StatutSortieIa::Termine ? $sortieActive->contenu : null;
        $enCours = $sorties->contains(fn (AiOutput $s) => $s->statut->enCours());
        $exportsPlan = $plans->formatsExportReecriture($compte);

        return view('livewire.reecrire-transcription', [
            // Pas « formats » : c'est le nom de la propriété cochée, qui masquerait cette variable.
            'formatsDisponibles' => FormatReecriture::cases(),
            'sorties' => $sorties,
            'onglets' => $onglets,
            'ongletActif' => $ongletActif,
            'sortieActive' => $sortieActive,
            'contenu' => $contenu,
            'messagesNumerotes' => $contenu !== null && $ongletActif === FormatReecriture::Thread->value
                ? $exporteur->numerotes($contenu['messages'])
                : [],
            'enCours' => $enCours,
            'peutLancer' => $plans->creditsIaMensuels($plan) > 0,
            'cout' => $lancer->coutPour($transcription, $this->formatsChoisis()),
            'coutParFormat' => $lancer->coutParFormat(),
            'langues' => (array) config('voxia.langues_forcables'),
            'tons' => TonReecriture::cases(),
            'longueurs' => LongueurReecriture::cases(),
            'motifRefus' => $this->refus,
            'exports' => FormatExportReecriture::cases(),
            'peutExporter' => fn (FormatExportReecriture $export) => in_array($export, $exportsPlan, true),
            'planDebloquant' => fn (FormatExportReecriture $export) => $this->planDebloquant($plans, $export),
            'urlExport' => fn (FormatReecriture $format, FormatExportReecriture $export) => URL::temporarySignedRoute(
                'transcriptions.reecriture.export',
                now()->addMinutes((int) config('voxia.telechargement.duree_lien_minutes')),
                [$transcription, $format, $export],
            ),
            'libelleFormat' => fn (FormatReecriture $format) => __('reecriture.formats.'.$format->value),
            'nomPlan' => fn (Plan $p) => $plans->libellePlan($p),
        ]);
    }

    /** @return list<FormatReecriture> */
    private function formatsChoisis(): array
    {
        return array_values(array_filter(array_map(
            fn (string $valeur) => FormatReecriture::tryFrom($valeur),
            $this->formats,
        )));
    }

    /** Plan vendable le moins cher dont les exports de réécriture incluent ce format. */
    private function planDebloquant(PlanFeatures $plans, FormatExportReecriture $export): ?Plan
    {
        foreach ($plans->plansVendables() as $plan) {
            if (in_array($export->value, (array) config("voxia.plans.{$plan->value}.exports_reecriture", []), true)) {
                return $plan;
            }
        }

        return null;
    }

    private function langueParDefaut(Transcription $transcription): string
    {
        $langues = (array) config('voxia.langues_forcables');
        $candidate = $transcription->langue_forcee ?? $transcription->langue_detectee;

        return in_array($candidate, $langues, true) ? (string) $candidate : (string) $langues[0];
    }

    private function transcription(): Transcription
    {
        // Toujours filtré sur le compte connecté : une autre transcription répond 404.
        return Auth::user()->transcriptions()->findOrFail($this->transcriptionId);
    }
}
