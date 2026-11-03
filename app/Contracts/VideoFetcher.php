<?php

namespace App\Contracts;

use App\Data\AudioTelecharge;
use App\Data\LienVideo;
use App\Data\MetadonneesVideo;
use App\Exceptions\ErreurVideo;

/**
 * Récupération d'une vidéo distante (YouTube, TikTok) : reconnaissance du lien,
 * métadonnées et audio. L'implémentation réelle s'appuie sur yt-dlp ; les tests
 * utilisent App\Services\Video\FauxVideoFetcher.
 */
interface VideoFetcher
{
    /**
     * Reconnaît un lien YouTube ou TikTok. Tout autre lien est refusé.
     *
     * @throws ErreurVideo motif « lien_invalide »
     */
    public function reconnaitre(string $url): LienVideo;

    /**
     * Lit titre, durée, miniature, chaîne et statut live, sans rien télécharger.
     *
     * @throws ErreurVideo
     */
    public function metadonnees(LienVideo $lien): MetadonneesVideo;

    /**
     * Télécharge la meilleure piste audio seule, dans un dossier propre à la tâche.
     * Le dossier est supprimé en cas d'échec ; sinon l'appelant le supprime via
     * AudioTelecharge::supprimer().
     *
     * @throws ErreurVideo
     */
    public function telecharger(LienVideo $lien): AudioTelecharge;
}
