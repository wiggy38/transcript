<?php

namespace App\Services\Quota;

use App\Exceptions\ErreurVideo;
use App\Models\User;

/**
 * Vérifie que l'utilisateur peut lancer une transcription (plan, quota restant).
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
}
