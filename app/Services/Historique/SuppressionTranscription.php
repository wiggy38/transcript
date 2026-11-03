<?php

namespace App\Services\Historique;

use App\Jobs\SupprimerFichiersTranscriptions;
use App\Models\Transcription;
use Illuminate\Support\Facades\DB;

/**
 * Suppression de transcriptions (choix de l'utilisateur, conservation du plan Gratuit).
 *
 * Les lignes partent dans une transaction : mots, paragraphes, analyses et journaux suivent par
 * cascade. Le MP3 et le dossier de la transcription partent ensuite par la file, pour que la
 * requête n'attende pas le stockage. Une transcription supprimée n'apparaît plus ni dans la
 * liste, ni dans la recherche, puisque ses paragraphes n'existent plus.
 */
class SuppressionTranscription
{
    /** @param  list<int>  $identifiants */
    public function supprimer(array $identifiants): int
    {
        if ($identifiants === []) {
            return 0;
        }

        $supprimees = DB::transaction(
            fn () => Transcription::query()->whereIn('id', $identifiants)->delete()
        );

        SupprimerFichiersTranscriptions::dispatch(array_values($identifiants));

        return $supprimees;
    }

    public function supprimerUne(Transcription $transcription): void
    {
        $this->supprimer([$transcription->getKey()]);
    }
}
