<?php

namespace App\Contracts;

use App\Data\TranscriptionBrute;
use App\Exceptions\ErreurVideo;

/**
 * Transcription d'un fichier audio (F2). Le résultat est neutre : aucun type ni
 * champ du fournisseur ne sort de l'implémentation.
 */
interface TranscriptionEngine
{
    /**
     * @param  string  $cheminAudio  Chemin du MP3 produit par F3.
     * @param  int  $dureeSecondes  Durée du MP3, pour le coût estimé.
     * @param  string|null  $langueForcee  Code de langue choisi par l'utilisateur, sinon détection automatique.
     *
     * @throws ErreurVideo motif « erreur_technique » après les tentatives
     */
    public function transcrire(
        string $cheminAudio,
        int $dureeSecondes,
        ?string $langueForcee = null,
    ): TranscriptionBrute;
}
