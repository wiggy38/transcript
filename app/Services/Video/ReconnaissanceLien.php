<?php

namespace App\Services\Video;

use App\Data\LienVideo;
use App\Enums\MotifEchec;
use App\Enums\SourceTranscription;
use App\Exceptions\ErreurVideo;

/**
 * Reconnaît les liens acceptés : YouTube (watch, youtu.be, Shorts) et TikTok
 * (vidéo publique, vm.tiktok.com). Tout le reste est un lien invalide.
 */
final class ReconnaissanceLien
{
    private const HOTES_YOUTUBE = ['youtube.com', 'www.youtube.com', 'm.youtube.com'];

    private const HOTES_TIKTOK = ['tiktok.com', 'www.tiktok.com', 'm.tiktok.com'];

    private const ID_YOUTUBE = '/^[A-Za-z0-9_-]{11}$/';

    public function reconnaitre(string $url): LienVideo
    {
        $parties = parse_url(trim($url));

        if (
            $parties === false
            || ! isset($parties['scheme'], $parties['host'])
            || ! in_array(strtolower($parties['scheme']), ['http', 'https'], true)
            || isset($parties['port'], $parties['user'], $parties['pass'])
        ) {
            throw $this->invalide();
        }

        $hote = strtolower($parties['host']);
        $chemin = rtrim($parties['path'] ?? '', '/');
        parse_str($parties['query'] ?? '', $requete);

        return match (true) {
            in_array($hote, self::HOTES_YOUTUBE, true) => $this->youtube($chemin, $requete),
            $hote === 'youtu.be' => $this->youtubeCourt($chemin),
            in_array($hote, self::HOTES_TIKTOK, true) => $this->tiktok($chemin),
            $hote === 'vm.tiktok.com' => $this->tiktokCourt($chemin),
            default => throw $this->invalide(),
        };
    }

    /** @param  array<string, mixed>  $requete */
    private function youtube(string $chemin, array $requete): LienVideo
    {
        if ($chemin === '/watch' && is_string($requete['v'] ?? null) && $this->idYoutubeValide($requete['v'])) {
            return $this->youtubeVideo($requete['v']);
        }

        if (preg_match('#^/shorts/([^/]+)$#', $chemin, $m) === 1 && $this->idYoutubeValide($m[1])) {
            return $this->youtubeVideo($m[1]);
        }

        throw $this->invalide();
    }

    private function youtubeCourt(string $chemin): LienVideo
    {
        if (preg_match('#^/([^/]+)$#', $chemin, $m) === 1 && $this->idYoutubeValide($m[1])) {
            return $this->youtubeVideo($m[1]);
        }

        throw $this->invalide();
    }

    private function tiktok(string $chemin): LienVideo
    {
        if (preg_match('#^/@([A-Za-z0-9._]+)/video/(\d+)$#', $chemin, $m) !== 1) {
            throw $this->invalide();
        }

        return new LienVideo(
            SourceTranscription::Tiktok,
            $m[2],
            'https://www.tiktok.com/@'.$m[1].'/video/'.$m[2],
        );
    }

    private function tiktokCourt(string $chemin): LienVideo
    {
        if (preg_match('#^/([A-Za-z0-9]+)$#', $chemin, $m) !== 1) {
            throw $this->invalide();
        }

        return new LienVideo(SourceTranscription::Tiktok, null, 'https://vm.tiktok.com/'.$m[1].'/');
    }

    private function youtubeVideo(string $id): LienVideo
    {
        return new LienVideo(
            SourceTranscription::Youtube,
            $id,
            'https://www.youtube.com/watch?v='.$id,
        );
    }

    private function idYoutubeValide(string $id): bool
    {
        return preg_match(self::ID_YOUTUBE, $id) === 1;
    }

    private function invalide(): ErreurVideo
    {
        return new ErreurVideo(MotifEchec::LienInvalide, 'lien non reconnu');
    }
}
