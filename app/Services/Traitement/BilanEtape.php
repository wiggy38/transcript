<?php

namespace App\Services\Traitement;

/** Ce qu'une étape ajoute à sa ligne de journal : un message et un coût éventuel. */
final class BilanEtape
{
    public ?string $message = null;

    public ?float $cout = null;
}
