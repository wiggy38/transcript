<?php

namespace App\Data;

final readonly class ExtraitAudio
{
    public function __construct(
        /** Chemin complet du MP3 produit. */
        public string $chemin,
        /** Nom du fichier (sans dossier), valide sous Windows, macOS et Linux. */
        public string $nomFichier,
        /** Durée mesurée avec ffprobe sur le MP3 produit, en secondes entières. */
        public int $dureeSecondes,
        /** Durée de la source avant découpe (0 si ffprobe ne la donne pas). */
        public int $dureeSourceSecondes = 0,
    ) {}
}
