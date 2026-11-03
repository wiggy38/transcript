<?php

namespace App\Jobs;

use App\Contracts\VideoFetcher;
use App\Enums\MotifEchec;
use App\Exceptions\ErreurVideo;
use App\Services\Video\ApercuLien;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Lit les métadonnées d'un lien pour l'aperçu de l'écran (titre, durée, miniature, chaîne).
 * Rien n'est téléchargé et rien n'est décompté. Le résultat va dans le cache (ApercuLien).
 */
class RecupererApercu implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public string $url) {}

    public function handle(VideoFetcher $videos, ApercuLien $apercus): void
    {
        try {
            $lien = $videos->reconnaitre($this->url);
            $metadonnees = $videos->metadonnees($lien);

            if ($metadonnees->estLive) {
                throw new ErreurVideo(MotifEchec::LiveEnCours, 'live');
            }

            $apercus->enregistrer($this->url, [
                'etat' => ApercuLien::PRET,
                'donnees' => [
                    'titre' => $metadonnees->titre,
                    'duree_secondes' => $metadonnees->dureeSecondes,
                    'miniature_url' => $metadonnees->miniatureUrl,
                    'chaine' => $metadonnees->chaine,
                    'id_video' => $metadonnees->id,
                ],
            ]);
        } catch (ErreurVideo $erreur) {
            $apercus->enregistrer($this->url, ['etat' => ApercuLien::ERREUR, 'motif' => $erreur->motif->value]);
        } catch (Throwable $erreur) {
            Log::error('aperçu : erreur inattendue', ['exception' => $erreur]);

            $apercus->enregistrer($this->url, ['etat' => ApercuLien::ERREUR, 'motif' => MotifEchec::ErreurTechnique->value]);
        }
    }
}
