<?php

use App\Enums\MotifEchec;
use App\Services\Video\TraducteurErreurYtdlp;

// Critère F1 : privée, supprimée, bloquée dans la région, live en cours, lien invalide,
// erreur technique. Les sorties viennent de tests/fixtures/ytdlp.

it('traduit une sortie yt-dlp enregistrée en motif clair', function (string $fichier, MotifEchec $motif) {
    expect(TraducteurErreurYtdlp::depuisSortie(fixtureYtdlp($fichier)))->toBe($motif);
})->with([
    'vidéo privée' => ['video_privee.txt', MotifEchec::VideoPrivee],
    'vidéo supprimée' => ['video_supprimee.txt', MotifEchec::VideoSupprimee],
    'bloquée dans la région' => ['bloquee_region.txt', MotifEchec::BloqueeRegion],
    'live en cours' => ['live_en_cours.txt', MotifEchec::LiveEnCours],
    'live filtré (!is_live)' => ['live_filtre.txt', MotifEchec::LiveEnCours],
    'lien invalide' => ['lien_invalide.txt', MotifEchec::LienInvalide],
    'erreur technique' => ['erreur_technique.txt', MotifEchec::ErreurTechnique],
    'titre trompeur hors ligne ERROR' => ['erreur_technique_titre_trompeur.txt', MotifEchec::ErreurTechnique],
]);

it('traite une sortie vide ou inconnue comme erreur technique', function () {
    expect(TraducteurErreurYtdlp::depuisSortie(''))->toBe(MotifEchec::ErreurTechnique)
        ->and(TraducteurErreurYtdlp::depuisSortie('ERROR: quelque chose d’inattendu'))->toBe(MotifEchec::ErreurTechnique);
});
