<?php

namespace App\Contracts;

use App\Data\ExtraitAudio;
use App\Exceptions\ErreurVideo;

/**
 * Extraction de l'audio en MP3 (F3) à partir d'une piste téléchargée ou d'un
 * fichier importé. Le contenu réel est vérifié, jamais l'extension.
 */
interface AudioExtractor
{
    /**
     * Produit un MP3 (débit : voxia.audio.mp3_bitrate_kbps) dans $dossierSortie.
     *
     * @param  string  $source  Chemin du fichier audio ou vidéo.
     * @param  string  $titre  Titre à partir duquel le nom du fichier est construit.
     * @param  int|null  $decoupeSecondes  Ne garder que les N premières secondes (null = tout).
     *
     * @throws ErreurVideo motif « format_non_supporte », « sans_audio » ou « erreur_technique »
     */
    public function extraire(
        string $source,
        string $dossierSortie,
        string $titre,
        ?int $decoupeSecondes = null,
    ): ExtraitAudio;
}
