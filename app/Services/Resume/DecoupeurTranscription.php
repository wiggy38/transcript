<?php

namespace App\Services\Resume;

use App\Data\Resume\ParagrapheResume;

/**
 * Découpe une transcription longue en morceaux de durée donnée. Un paragraphe n'est jamais coupé : un
 * morceau commence au premier paragraphe qui atteint la durée visée.
 */
final class DecoupeurTranscription
{
    /**
     * @param  list<ParagrapheResume>  $paragraphes  dans l'ordre de la vidéo
     * @return list<list<ParagrapheResume>>
     */
    public function decouper(array $paragraphes, int $tailleMs): array
    {
        $morceaux = [];
        $courant = [];
        $debutCourant = 0;

        foreach ($paragraphes as $paragraphe) {
            if ($courant !== [] && $paragraphe->debutMs - $debutCourant >= $tailleMs) {
                $morceaux[] = $courant;
                $courant = [];
            }

            if ($courant === []) {
                $debutCourant = $paragraphe->debutMs;
            }

            $courant[] = $paragraphe;
        }

        if ($courant !== []) {
            $morceaux[] = $courant;
        }

        return $morceaux;
    }
}
