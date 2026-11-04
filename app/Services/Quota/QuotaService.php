<?php

namespace App\Services\Quota;

use App\Enums\UniteConsommation;
use App\Exceptions\ErreurVideo;
use App\Models\ScriptAnalysis;
use App\Models\UsageRecord;
use App\Models\User;

/**
 * Vérifie que l'utilisateur peut lancer une transcription (plan, quota restant), et décompte les
 * crédits IA de l'analyse de script (F7).
 *
 * Provisoire : autorise tout tant que le décompte n'est pas écrit (cahier §5, étape 4).
 * Appelé avant chaque création de transcription, jamais pour une transcription déjà
 * existante. Une erreur technique ne doit jamais décompter : le décompte se fera sur
 * TranscriptionCompleted, pas ici.
 */
class QuotaService
{
    /**
     * @param  int|null  $dureeSecondes  durée connue avant lancement (nulle pour un lien, lue ensuite)
     *
     * @throws ErreurVideo quand le quota est épuisé (non levé pour l'instant)
     */
    public function verifier(User $utilisateur, ?int $dureeSecondes = null): void
    {
        // Volontairement vide : voir la note ci-dessus.
    }

    /**
     * Point d'accroche du crédit IA, appelé avant de lancer une analyse de script. Non bloquant
     * tant que l'étape 4 (plans, décompte) n'est pas branchée.
     *
     * @throws ErreurVideo quand le crédit manque (non levé pour l'instant)
     */
    public function verifierCreditIa(User $utilisateur): void
    {
        // Volontairement vide : voir la note ci-dessus.
    }

    /**
     * Décompte d'une analyse réussie : une ligne au journal de consommation, jamais pour un échec.
     */
    public function consommerCreditIa(User $utilisateur, ScriptAnalysis $analyse): UsageRecord
    {
        return UsageRecord::create([
            'user_id' => $utilisateur->getKey(),
            'transcription_id' => $analyse->transcription_id,
            'script_analysis_id' => $analyse->getKey(),
            'unite' => UniteConsommation::CreditIa,
            'quantite' => (int) config('voxia.script_analysis.credits_per_analysis'),
            'motif' => 'analyse_script',
        ]);
    }
}
