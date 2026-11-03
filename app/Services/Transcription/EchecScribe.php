<?php

namespace App\Services\Transcription;

use RuntimeException;

/**
 * Échec d'un appel à ElevenLabs, à l'intérieur du moteur. Il ne sort jamais du
 * moteur : après les tentatives, il devient une ErreurVideo « erreur_technique ».
 */
final class EchecScribe extends RuntimeException
{
    public function __construct(
        public readonly string $motif,
        public readonly ?int $statut = null,
        public readonly ?string $corps = null,
    ) {
        parent::__construct($motif);
    }
}
