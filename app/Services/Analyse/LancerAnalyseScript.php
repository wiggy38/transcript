<?php

namespace App\Services\Analyse;

use App\Enums\StatutAnalyseScript;
use App\Enums\StatutTranscription;
use App\Exceptions\AnalyseRefusee;
use App\Jobs\AnalyserScript;
use App\Models\ScriptAnalysis;
use App\Models\Transcription;
use App\Models\User;
use App\Services\Quota\QuotaService;
use App\Services\Traitement\FileTraitement;

/**
 * Lance l'analyse d'une transcription (F7). Les règles sont vérifiées ici, côté serveur, et non
 * seulement par le bouton de l'écran : durée maximale, transcription terminée, propriétaire.
 *
 * Une analyse en cours ou réussie n'est jamais relancée (un second crédit serait décompté). Une
 * analyse en échec repart sur la même ligne (transcription_id est unique).
 */
final class LancerAnalyseScript
{
    public function __construct(private readonly QuotaService $quotas) {}

    /**
     * @throws AnalyseRefusee
     */
    public function lancer(User $utilisateur, Transcription $transcription): ScriptAnalysis
    {
        if ($transcription->user_id !== $utilisateur->getKey()) {
            throw new AnalyseRefusee(AnalyseRefusee::NON_TERMINEE);
        }

        if ($transcription->statut !== StatutTranscription::Termine) {
            throw new AnalyseRefusee(AnalyseRefusee::NON_TERMINEE);
        }

        if ($transcription->duree_secondes === null) {
            throw new AnalyseRefusee(AnalyseRefusee::DUREE_INCONNUE);
        }

        if ((int) $transcription->duree_secondes > (int) config('voxia.script_analysis.max_video_seconds')) {
            throw new AnalyseRefusee(AnalyseRefusee::DUREE_TROP_LONGUE);
        }

        $existante = $transcription->scriptAnalysis;

        if ($existante !== null && in_array($existante->statut, [StatutAnalyseScript::EnAttente, StatutAnalyseScript::Analyse, StatutAnalyseScript::Termine], true)) {
            return $existante;
        }

        $this->quotas->verifierCreditIa($utilisateur);

        $analyse = ScriptAnalysis::updateOrCreate(
            ['transcription_id' => $transcription->getKey()],
            ['statut' => StatutAnalyseScript::EnAttente, 'fournisseur' => null, 'date_debut' => null, 'date_termine' => null, 'date_echec' => null],
        );

        AnalyserScript::dispatch($analyse->getKey())
            ->onQueue(FileTraitement::pour($utilisateur->plan));

        return $analyse;
    }
}
