<?php

namespace App\Services\Paiement;

use App\Contracts\BillingProvider;
use App\Data\Paiement\Ouverture;
use App\Models\User;
use Carbon\CarbonInterface;

/**
 * Fausse implémentation de BillingProvider pour les tests (voir tests/TestCase.php). Aucun appel
 * réseau : chaque opération est notée dans $appels. Les factures renvoyées se règlent par $factures.
 */
final class FauxBillingProvider implements BillingProvider
{
    /** @var list<array{operation: string, utilisateur: int, valeur: mixed}> */
    public array $appels = [];

    /** @var list<array{id: string, numero: ?string, date: CarbonInterface, montant: string, statut: string}> */
    public array $factures = [];

    public function ouvrirPaiement(User $utilisateur, string $formule, ?int $transcriptionId = null, ?string $retour = null): Ouverture
    {
        $valeur = [$formule, $transcriptionId];

        if ($retour !== null) {
            $valeur[] = $retour;
        }

        $this->appels[] = ['operation' => 'ouvrir', 'utilisateur' => $utilisateur->getKey(), 'valeur' => $valeur];

        return new Ouverture('faux', 'sandbox', 'jeton-faux', [
            'items' => [['priceId' => $formule]],
            'customData' => array_filter(['formule' => $formule, 'transcription_id' => $transcriptionId]),
        ]);
    }

    public function changerFormule(User $utilisateur, string $formule): void
    {
        $this->appels[] = ['operation' => 'changer', 'utilisateur' => $utilisateur->getKey(), 'valeur' => $formule];
    }

    public function resilier(User $utilisateur): void
    {
        $this->appels[] = ['operation' => 'resilier', 'utilisateur' => $utilisateur->getKey(), 'valeur' => null];
    }

    public function annulerResiliation(User $utilisateur): void
    {
        $this->appels[] = ['operation' => 'annuler_resiliation', 'utilisateur' => $utilisateur->getKey(), 'valeur' => null];
    }

    public function factures(User $utilisateur): array
    {
        return $this->factures;
    }

    public function urlFacture(User $utilisateur, string $idFacture): ?string
    {
        $this->appels[] = ['operation' => 'facture', 'utilisateur' => $utilisateur->getKey(), 'valeur' => $idFacture];

        return 'https://faux.test/factures/'.$idFacture;
    }
}
