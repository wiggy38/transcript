<?php

namespace App\Livewire;

use App\Enums\FormatExport;
use App\Enums\StatutSortieIa;
use App\Exceptions\ResumeRefuse;
use App\Models\AiOutput;
use App\Models\Transcription;
use App\Services\Exports\ExporteurResume;
use App\Services\Plans\PlanFeatures;
use App\Services\Resume\LancerResume;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Livewire\Component;

/**
 * Résumé d'une transcription (V2) : résumé, points clés et chapitres, à côté de la lecture.
 *
 * Le coût est affiché avant le lancement. Régénérer demande une confirmation. Un clic sur un point ou un
 * chapitre envoie « voxia-aller » : le lecteur lance la lecture à cet instant (comme les horodatages).
 */
class ResumeTranscription extends Component
{
    public int $transcriptionId;

    public string $langue = 'fr';

    /** Motif d'un lancement refusé (voir ResumeRefuse), ou null. */
    public ?string $refus = null;

    public bool $confirmerRegeneration = false;

    public function mount(Transcription $transcription, LancerResume $lancer): void
    {
        $this->transcriptionId = $transcription->getKey();
        $this->langue = $lancer->langueParDefaut($transcription);
    }

    public function lancer(LancerResume $lancer): void
    {
        $this->refus = null;

        try {
            $lancer->lancer(Auth::user(), $this->transcription(), $this->langue);
        } catch (ResumeRefuse $erreur) {
            $this->refus = $erreur->motif;
        }
    }

    public function demanderRegeneration(): void
    {
        $this->refus = null;
        $this->confirmerRegeneration = true;
    }

    public function annulerRegeneration(): void
    {
        $this->confirmerRegeneration = false;
    }

    /** Régénérer ne part qu'après confirmation (confirmerRegeneration), et décompte de nouveaux crédits. */
    public function regenerer(LancerResume $lancer): void
    {
        $this->refus = null;

        try {
            $lancer->lancer(Auth::user(), $this->transcription(), $this->langue, regenerer: true, confirme: true);
        } catch (ResumeRefuse $erreur) {
            $this->refus = $erreur->motif;
        }

        $this->confirmerRegeneration = false;
    }

    public function render(PlanFeatures $plans, LancerResume $lancer, ExporteurResume $exporteur)
    {
        $transcription = $this->transcription();
        $compte = Auth::user();
        $sortie = $transcription->aiOutputs()->where('fonctionnalite', 'resume')->first();
        $statut = $sortie?->statut;
        $contenu = $this->contenu($sortie);
        $cout = $lancer->coutPour($transcription);
        $expiration = now()->addMinutes((int) config('voxia.telechargement.duree_lien_minutes'));

        return view('livewire.resume-transcription', [
            'sortie' => $sortie,
            'statut' => $statut,
            'enCours' => $statut?->enCours() ?? false,
            'termine' => $statut === StatutSortieIa::Termine,
            'contenu' => $contenu,
            'peutLancer' => $plans->creditsIaMensuels($plans->planDe($compte)) > 0,
            'cout' => $cout,
            'langues' => (array) config('voxia.langues_forcables'),
            'motifRefus' => $this->refus ?? $this->motifDuree($transcription),
            'texteCopie' => $contenu !== null ? $exporteur->txt($contenu) : '',
            'formats' => [FormatExport::Txt, FormatExport::Pdf, FormatExport::Docx],
            'peutExporter' => fn (FormatExport $format) => $plans->peutExporter($compte, $format),
            'libellePlanDebloquant' => function (FormatExport $format) use ($plans) {
                $plan = $plans->planMinimalPour($format);

                return $plan !== null ? $plans->libellePlan($plan) : '';
            },
            'urlExport' => fn (FormatExport $format) => URL::temporarySignedRoute('transcriptions.resume.export', $expiration, [$transcription, $format]),
        ]);
    }

    /** @return array{resume: string, points: list<array<string, mixed>>, chapitres: list<array<string, mixed>>}|null */
    private function contenu(?AiOutput $sortie): ?array
    {
        $contenu = $sortie?->contenu;

        return is_array($contenu) && ($contenu['resume'] ?? '') !== '' ? $contenu : null;
    }

    private function motifDuree(Transcription $transcription): ?string
    {
        return $transcription->duree_secondes === null ? ResumeRefuse::DUREE_INCONNUE : null;
    }

    private function transcription(): Transcription
    {
        // Toujours filtré sur le compte connecté : une autre transcription répond 404.
        return Auth::user()->transcriptions()->findOrFail($this->transcriptionId);
    }
}
