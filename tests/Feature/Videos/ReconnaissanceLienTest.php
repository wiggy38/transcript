<?php

use App\Enums\MotifEchec;
use App\Enums\SourceTranscription;
use App\Exceptions\ErreurVideo;
use App\Services\Video\ReconnaissanceLien;

// Critère F1 : liens acceptés (YouTube, Shorts, TikTok public, vm.tiktok.com) et refus du reste.

it('reconnaît les liens YouTube et TikTok acceptés', function (string $url, SourceTranscription $source, ?string $idVideo, string $urlCanonique) {
    $lien = (new ReconnaissanceLien)->reconnaitre($url);

    expect($lien->source)->toBe($source)
        ->and($lien->idVideo)->toBe($idVideo)
        ->and($lien->url)->toBe($urlCanonique);
})->with([
    'YouTube watch' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ', SourceTranscription::Youtube, 'dQw4w9WgXcQ', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
    'YouTube watch avec temps et liste' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ&list=PLabc123&index=2&t=90s', SourceTranscription::Youtube, 'dQw4w9WgXcQ', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
    'youtu.be' => ['https://youtu.be/dQw4w9WgXcQ', SourceTranscription::Youtube, 'dQw4w9WgXcQ', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
    'youtu.be avec si et temps' => ['https://youtu.be/dQw4w9WgXcQ?si=AbCdEf12&t=42', SourceTranscription::Youtube, 'dQw4w9WgXcQ', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
    'Shorts' => ['https://www.youtube.com/shorts/dQw4w9WgXcQ', SourceTranscription::Youtube, 'dQw4w9WgXcQ', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
    'YouTube mobile' => ['https://m.youtube.com/watch?v=dQw4w9WgXcQ', SourceTranscription::Youtube, 'dQw4w9WgXcQ', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
    'TikTok vidéo publique' => ['https://www.tiktok.com/@creator.name/video/7234567890123456789', SourceTranscription::Tiktok, '7234567890123456789', 'https://www.tiktok.com/@creator.name/video/7234567890123456789'],
    'TikTok avec paramètres' => ['https://www.tiktok.com/@creator/video/7234567890123456789?is_from_webapp=1&sender_device=pc', SourceTranscription::Tiktok, '7234567890123456789', 'https://www.tiktok.com/@creator/video/7234567890123456789'],
    'TikTok lien court' => ['https://vm.tiktok.com/ZMabc123/', SourceTranscription::Tiktok, null, 'https://vm.tiktok.com/ZMabc123/'],
]);

it('refuse tout le reste comme lien invalide', function (string $url) {
    expect(fn () => (new ReconnaissanceLien)->reconnaitre($url))
        ->toThrow(fn (ErreurVideo $erreur) => $erreur->motif === MotifEchec::LienInvalide);
})->with([
    'watch sans identifiant' => ['https://www.youtube.com/watch'],
    'playlist seule' => ['https://www.youtube.com/playlist?list=PLabc123'],
    'chaîne YouTube' => ['https://www.youtube.com/@chaine'],
    'autre plateforme' => ['https://vimeo.com/123456789'],
    'profil TikTok' => ['https://www.tiktok.com/@creator'],
    'schéma non HTTP' => ['ftp://youtube.com/watch?v=dQw4w9WgXcQ'],
    'texte seul' => ['dQw4w9WgXcQ'],
]);
