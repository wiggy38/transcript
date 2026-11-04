<?php

namespace App\Data\Paiement;

/**
 * Ce qu'il faut à la page pour ouvrir le paiement chez le prestataire : son nom, l'environnement
 * (sandbox ou production), le jeton public d'initialisation et les options de l'ouverture.
 * Le jeton est public par conception : jamais de clé d'API ici.
 */
final readonly class Ouverture
{
    /** @param  array<string, mixed>  $options  options passées à l'ouverture du paiement (articles, client, données) */
    public function __construct(
        public string $fournisseur,
        public string $environnement,
        public string $jeton,
        public array $options,
    ) {}

    /** @return array{fournisseur: string, environnement: string, jeton: string, options: array<string, mixed>} */
    public function versTableau(): array
    {
        return [
            'fournisseur' => $this->fournisseur,
            'environnement' => $this->environnement,
            'jeton' => $this->jeton,
            'options' => $this->options,
        ];
    }
}
