<?php

namespace App\Data;

use App\Enums\SourceTranscription;

/**
 * Lien reconnu. Pour un lien court TikTok (vm.tiktok.com), l'identifiant n'est
 * connu qu'après résolution : il reste nul jusqu'aux métadonnées.
 */
final readonly class LienVideo
{
    public function __construct(
        public SourceTranscription $source,
        public ?string $idVideo,
        public string $url,
    ) {}
}
