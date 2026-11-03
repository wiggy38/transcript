<?php

namespace App\Services\Transcription;

use App\Contracts\TranscriptionEngine;
use App\Data\TranscriptionBrute;
use App\Exceptions\ErreurVideo;

/**
 * Fausse implémentation de TranscriptionEngine, utilisée par tous les tests (voir
 * tests/TestCase.php). Aucun appel réseau. Les réponses se mettent en file ; sans
 * réponse en file, le moteur renvoie une transcription vide. Chaque appel est noté
 * dans $appels pour les assertions.
 */
final class FakeTranscriptionEngine implements TranscriptionEngine
{
    /** @var list<TranscriptionBrute|ErreurVideo> */
    private array $file = [];

    /** @var list<array{chemin: string, duree: int, langue: ?string}> */
    public array $appels = [];

    public function repondre(TranscriptionBrute $reponse): self
    {
        $this->file[] = $reponse;

        return $this;
    }

    public function echouer(ErreurVideo $erreur): self
    {
        $this->file[] = $erreur;

        return $this;
    }

    public function transcrire(
        string $cheminAudio,
        int $dureeSecondes,
        ?string $langueForcee = null,
    ): TranscriptionBrute {
        $this->appels[] = ['chemin' => $cheminAudio, 'duree' => $dureeSecondes, 'langue' => $langueForcee];

        $suivant = array_shift($this->file);

        if ($suivant instanceof ErreurVideo) {
            throw $suivant;
        }

        return $suivant ?? new TranscriptionBrute(null, [], '', 0.0);
    }
}
