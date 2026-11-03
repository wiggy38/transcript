<?php

namespace App\Services\Traitement;

use App\Contracts\VideoFetcher;
use App\Enums\MotifEchec;
use App\Enums\SourceTranscription;
use App\Enums\StatutTranscription;
use App\Exceptions\ErreurVideo;
use App\Jobs\ProcessTranscription;
use App\Models\Transcription;
use App\Models\User;
use InvalidArgumentException;

/**
 * Crée la transcription (lien ou fichier local) et la met en file. Aucune attente
 * ici : le traitement se fait dans la tâche ProcessTranscription.
 */
final class LancerTranscription
{
    public function __construct(
        private readonly VideoFetcher $videos,
    ) {}

    /**
     * @throws ErreurVideo motif « lien_invalide » ou « format_non_supporte »
     * @throws InvalidArgumentException fichier introuvable
     */
    public function lancer(User $utilisateur, string $source, ?string $langue = null): Transcription
    {
        $langue = $langue !== null && $langue !== '' ? $langue : null;

        if (preg_match('#^https?://#i', $source) === 1) {
            return $this->lancerLien($utilisateur, $source, $langue);
        }

        return $this->lancerFichier($utilisateur, $source, $langue);
    }

    private function lancerLien(User $utilisateur, string $source, ?string $langue): Transcription
    {
        $lien = $this->videos->reconnaitre($source);

        // Même utilisateur, même vidéo : on renvoie la transcription existante (F1).
        // Un lien court TikTok n'a pas encore d'identifiant : pas de contrôle possible ici.
        if ($lien->idVideo !== null) {
            $existante = $utilisateur->transcriptions()
                ->where('source', $lien->source)
                ->where('id_video', $lien->idVideo)
                ->first();

            if ($existante !== null) {
                return $this->reprendre($existante);
            }
        }

        $transcription = $utilisateur->transcriptions()->create([
            'source' => $lien->source,
            'url_origine' => $lien->url,
            'id_video' => $lien->idVideo,
            'langue_forcee' => $langue,
            'statut' => StatutTranscription::EnAttente,
        ]);

        $this->mettreEnFile($transcription, $utilisateur, null);

        return $transcription;
    }

    private function lancerFichier(User $utilisateur, string $source, ?string $langue): Transcription
    {
        if (! is_file($source) || ! is_readable($source)) {
            throw new InvalidArgumentException('fichier introuvable');
        }

        $extension = strtolower(pathinfo($source, PATHINFO_EXTENSION));

        if (! in_array($extension, (array) config('voxia.uploads.extensions'), true)) {
            throw new ErreurVideo(MotifEchec::FormatNonSupporte, '.'.$extension);
        }

        $transcription = $utilisateur->transcriptions()->create([
            'source' => SourceTranscription::Fichier,
            'titre' => mb_substr(pathinfo($source, PATHINFO_FILENAME), 0, 500),
            'langue_forcee' => $langue,
            'statut' => StatutTranscription::EnAttente,
        ]);

        $this->mettreEnFile($transcription, $utilisateur, realpath($source) ?: $source);

        return $transcription;
    }

    /**
     * Transcription déjà existante : terminée, on la renvoie ; en cours, on ne relance
     * rien ; en échec, on relance la même ligne.
     */
    private function reprendre(Transcription $transcription): Transcription
    {
        if (in_array($transcription->statut, [StatutTranscription::EnAttente, StatutTranscription::Telechargement, StatutTranscription::Transcription, StatutTranscription::Termine], true)) {
            return $transcription;
        }

        $transcription->update([
            'statut' => StatutTranscription::EnAttente,
            'motif_echec' => null,
            'date_echec' => null,
        ]);

        $this->mettreEnFile($transcription, $transcription->user, null);

        return $transcription;
    }

    private function mettreEnFile(Transcription $transcription, User $utilisateur, ?string $cheminFichier): void
    {
        ProcessTranscription::dispatch($transcription->getKey(), $cheminFichier)
            ->onQueue(FileTraitement::pour($utilisateur->plan));
    }
}
