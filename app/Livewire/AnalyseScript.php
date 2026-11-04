<?php

namespace App\Livewire;

use App\Enums\FormatExport;
use App\Enums\StatutAnalyseScript;
use App\Enums\StatutTranscription;
use App\Enums\TypePartieScript;
use App\Exceptions\AnalyseRefusee;
use App\Models\ScriptAnalysis;
use App\Models\ScriptPart;
use App\Models\Transcription;
use App\Services\Analyse\LancerAnalyseScript;
use App\Services\Plans\PlanFeatures;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Livewire\Component;

/**
 * Analyse de la structure d'un script publicitaire (F7), affichée sous la lecture d'une transcription.
 * Le bouton lance la tâche ; l'écran interroge le statut tant qu'elle tourne. Le résultat reprend la
 * maquette (barre proportionnelle, parties avec horodatages) ; un clic sur une partie envoie un
 * événement « voxia-aller » que le lecteur écoute pour lancer la lecture à son début.
 */
class AnalyseScript extends Component
{
    public int $transcriptionId;

    /** Motif d'un lancement refusé (voir AnalyseRefusee), ou null. */
    public ?string $refus = null;

    public function mount(Transcription $transcription): void
    {
        $this->transcriptionId = $transcription->getKey();
    }

    public function analyser(LancerAnalyseScript $lancer): void
    {
        $this->refus = null;

        try {
            $lancer->lancer(Auth::user(), $this->transcription());
        } catch (AnalyseRefusee $erreur) {
            $this->refus = $erreur->motif;
        }
    }

    public function render(PlanFeatures $plans)
    {
        $transcription = $this->transcription();
        $analyse = $transcription->scriptAnalysis?->load('parts');
        $maxSecondes = (int) config('voxia.script_analysis.max_video_seconds');
        $dureeTropLongue = $transcription->duree_secondes === null || (int) $transcription->duree_secondes > $maxSecondes;
        $termine = $analyse?->statut === StatutAnalyseScript::Termine;
        $enCours = in_array($analyse?->statut, [StatutAnalyseScript::EnAttente, StatutAnalyseScript::Analyse], true);

        // Motif affiché : celui du dernier lancement refusé, sinon la raison liée à la durée.
        $motifDuree = match (true) {
            $transcription->duree_secondes === null => AnalyseRefusee::DUREE_INCONNUE,
            $dureeTropLongue => AnalyseRefusee::DUREE_TROP_LONGUE,
            default => null,
        };
        $motifRefus = $this->refus ?? $motifDuree;

        $compte = Auth::user();
        $expiration = now()->addMinutes((int) config('voxia.telechargement.duree_lien_minutes'));

        return view('livewire.analyse-script', [
            'transcription' => $transcription,
            'termine' => $termine,
            'enCours' => $enCours,
            'bloque' => $dureeTropLongue || $transcription->statut !== StatutTranscription::Termine,
            'motifRefus' => $motifRefus,
            'minutes' => intdiv($maxSecondes, 60),
            'statut' => $analyse?->statut,
            'parties' => $this->parties($analyse),
            'formats' => [FormatExport::Txt, FormatExport::Pdf],
            'peutExporter' => fn (FormatExport $format) => $plans->peutExporter($compte, $format),
            'libellePlanDebloquant' => function (FormatExport $format) use ($plans) {
                $plan = $plans->planMinimalPour($format);

                return $plan !== null ? $plans->libellePlan($plan) : '';
            },
            'urlExport' => fn (FormatExport $format) => URL::temporarySignedRoute('transcriptions.analyse.export', $expiration, [$transcription, $format]),
        ]);
    }

    /**
     * Les cinq parties, dans l'ordre de la vidéo, avec leur poids dans la barre.
     *
     * @return list<array{type: TypePartieScript, detectee: bool, debut: ?int, fin: ?int, texte: ?string, poids: int}>
     */
    private function parties(?ScriptAnalysis $analyse): array
    {
        $poidsAbsente = (int) config('voxia.script_analysis.poids_partie_absente_ms');
        $parties = [];

        foreach (TypePartieScript::cases() as $type) {
            /** @var ScriptPart|null $partie */
            $partie = $analyse?->parts->first(fn (ScriptPart $p) => $p->type === $type);
            $detectee = $partie !== null && $partie->detectee;

            $parties[] = [
                'type' => $type,
                'detectee' => $detectee,
                'debut' => $detectee ? $partie->debut_ms : null,
                'fin' => $detectee ? $partie->fin_ms : null,
                'texte' => $detectee ? $partie->texte : null,
                // Une partie détectée pèse sa durée réelle ; une absente, un poids fixe (config).
                'poids' => $detectee ? max(1, $partie->fin_ms - $partie->debut_ms) : $poidsAbsente,
            ];
        }

        return $parties;
    }

    private function transcription(): Transcription
    {
        // Toujours filtré sur le compte connecté : une autre transcription répond 404.
        return Auth::user()->transcriptions()->findOrFail($this->transcriptionId);
    }
}
