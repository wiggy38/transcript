<?php

namespace App\Livewire;

use App\Enums\FormatExport;
use App\Enums\StatutSortieIa;
use App\Exceptions\TraductionRefusee;
use App\Models\AiOutput;
use App\Models\Transcription;
use App\Services\Plans\PlanFeatures;
use App\Services\Traduction\LancerTraduction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Livewire\Component;

/**
 * Traduction d'une transcription (V2) : une ou plusieurs langues cibles, coût affiché avant lancement.
 *
 * Le sélecteur choisit l'original ou une traduction terminée. Une traduction s'affiche côte à côte avec
 * l'original ; le segment en cours de lecture, signalé par le lecteur, est surligné des deux côtés.
 */
class TraduireTranscription extends Component
{
    public int $transcriptionId;

    /** Codes des langues cochées pour le prochain lancement. */
    public array $langues = [];

    /** « original » ou le code d'une langue traduite affichée. */
    public string $onglet = 'original';

    /** Motif d'un lancement refusé (voir TraductionRefusee), ou null. */
    public ?string $refus = null;

    public function mount(Transcription $transcription): void
    {
        $this->transcriptionId = $transcription->getKey();
    }

    public function lancer(LancerTraduction $lancer): void
    {
        $this->refus = null;

        if ($this->langues === []) {
            $this->refus = TraductionRefusee::AUCUNE_LANGUE;

            return;
        }

        try {
            $lancer->lancer(Auth::user(), $this->transcription(), $this->langues);
            $this->langues = [];
        } catch (TraductionRefusee $erreur) {
            $this->refus = $erreur->motif;
        }
    }

    public function render(PlanFeatures $plans, LancerTraduction $lancer)
    {
        $transcription = $this->transcription();
        $compte = Auth::user();
        $plan = $plans->planDe($compte);
        $source = $lancer->langueSource($transcription);

        $sorties = $transcription->aiOutputs()
            ->where('fonctionnalite', 'traduction')
            ->get()
            ->keyBy('format');

        // Langues proposées, dans l'ordre de la configuration ; seules les traductions terminées s'affichent.
        $terminees = [];

        foreach ($lancer->languesCibles($transcription) as $langue) {
            if (($sorties[$langue] ?? null)?->statut === StatutSortieIa::Termine) {
                $terminees[$langue] = $sorties[$langue];
            }
        }

        $ongletActif = array_key_exists($this->onglet, $terminees) ? $this->onglet : 'original';
        $sortieActive = $terminees[$ongletActif] ?? null;
        $segments = $sortieActive?->contenu['segments'] ?? [];

        // L'original est le même pour toutes les langues : on le prend dans la première sortie qui le porte.
        $originaux = $sorties->first(fn (AiOutput $s) => ! empty($s->contenu['segments'] ?? []))?->contenu['segments'] ?? [];

        return view('livewire.traduire-transcription', [
            'source' => $source,
            'languesCibles' => $lancer->languesCibles($transcription),
            'sorties' => $sorties,
            'terminees' => $terminees,
            'ongletActif' => $ongletActif,
            'sortieActive' => $sortieActive,
            'segments' => $segments,
            'originaux' => $originaux,
            'enCours' => $sorties->contains(fn (AiOutput $s) => $s->statut->enCours()),
            'peutLancer' => $plans->creditsIaMensuels($plan) > 0,
            'cout' => $lancer->coutPour($transcription, $this->langues),
            'coutParLangue' => $lancer->coutParLangue($transcription),
            'motifRefus' => $this->refus,
            'exports' => array_values(array_filter(
                FormatExport::cases(),
                fn (FormatExport $format) => $plans->peutExporter($compte, $format),
            )),
            'urlExport' => fn (string $langue, FormatExport $format, bool $horodatages = true) => URL::temporarySignedRoute(
                'transcriptions.traduction.export',
                now()->addMinutes((int) config('voxia.telechargement.duree_lien_minutes')),
                [
                    'transcription' => $transcription,
                    'langue' => $langue,
                    'export' => $format->value,
                    'horodatages' => $horodatages ? 1 : 0,
                ],
            ),
            'avecHorodatages' => fn (FormatExport $format) => in_array($format, [FormatExport::Txt, FormatExport::Pdf], true),
            'libelleLangue' => fn (string $code) => __('traduction.langues.'.$code),
        ]);
    }

    private function transcription(): Transcription
    {
        // Toujours filtré sur le compte connecté : une autre transcription répond 404.
        return Auth::user()->transcriptions()->findOrFail($this->transcriptionId);
    }
}
