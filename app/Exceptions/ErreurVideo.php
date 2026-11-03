<?php

namespace App\Exceptions;

use App\Enums\MotifEchec;
use RuntimeException;

/**
 * Erreur de récupération. Le motif est affiché à l'utilisateur via lang/ ; la
 * sortie brute de yt-dlp reste dans les journaux, jamais dans le message.
 */
class ErreurVideo extends RuntimeException
{
    public function __construct(
        public readonly MotifEchec $motif,
        string $detail = '',
    ) {
        parent::__construct(trim($motif->value.' '.$detail));
    }

    public function estTechnique(): bool
    {
        return $this->motif === MotifEchec::ErreurTechnique;
    }

    public function messageUtilisateur(): string
    {
        return __('videos.motifs.'.$this->motif->value);
    }
}
