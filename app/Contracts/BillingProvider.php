<?php

namespace App\Contracts;

use App\Data\Paiement\Ouverture;
use App\Exceptions\PaiementIndisponible;
use App\Models\User;
use Carbon\CarbonInterface;

/**
 * Paiement et abonnement (cahier §5), derrière une interface : le prestataire (aujourd'hui Paddle
 * via Laravel Cashier) n'apparaît que dans App\Services\Paiement\PaddleBillingProvider.
 *
 * Les formules sont désignées par leur clé de config (voxia.paiement.prix) : « pro_mensuel »,
 * « pro_annuel », « pack ». Cette interface ne fait que déclencher les opérations chez le
 * prestataire : l'état local (plan, Packs, factures) est mis à jour par les webhooks, pas ici.
 */
interface BillingProvider
{
    /**
     * Ouvre le paiement d'une formule. « transcriptionId » : aperçu partiel à relancer en version
     * complète après paiement (rappelé dans les données personnalisées du paiement). « retour » :
     * adresse de l'écran d'origine, rejointe après le paiement (chemin local, voir CheminLocal).
     *
     * @throws PaiementIndisponible formule sans identifiant de prix configuré
     */
    public function ouvrirPaiement(User $utilisateur, string $formule, ?int $transcriptionId = null, ?string $retour = null): Ouverture;

    /** Passe l'abonnement Pro en cours à une autre formule (mensuel ↔ annuel), au prorata. */
    public function changerFormule(User $utilisateur, string $formule): void;

    /** Résiliation : l'accès reste ouvert jusqu'à la fin de la période payée. */
    public function resilier(User $utilisateur): void;

    /** Annule une résiliation programmée, avant la fin de la période. */
    public function annulerResiliation(User $utilisateur): void;

    /**
     * Factures du compte, les plus récentes d'abord.
     *
     * @return list<array{id: string, numero: ?string, date: CarbonInterface, montant: string, statut: string}>
     */
    public function factures(User $utilisateur): array;

    /** Adresse temporaire du PDF d'une facture du compte, ou null si elle n'en a pas. */
    public function urlFacture(User $utilisateur, string $idFacture): ?string;
}
