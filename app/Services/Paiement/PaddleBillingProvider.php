<?php

namespace App\Services\Paiement;

use App\Contracts\BillingProvider;
use App\Data\Paiement\Ouverture;
use App\Exceptions\PaiementIndisponible;
use App\Models\User;

/**
 * Implémentation Paddle (Laravel Cashier Paddle). Seul endroit de l'application qui parle à Paddle.
 * Les montants facturés sont ceux des prix Paddle désignés dans config/voxia.php (paiement.prix) ;
 * les montants affichés (plans) viennent de la même config mais ne sont pas envoyés à Paddle.
 */
final class PaddleBillingProvider implements BillingProvider
{
    public const FOURNISSEUR = 'paddle';

    public function ouvrirPaiement(User $utilisateur, string $formule, ?int $transcriptionId = null, ?string $retour = null): Ouverture
    {
        $prix = self::prixDe($formule);

        if (! is_string(config('cashier.client_side_token')) || config('cashier.client_side_token') === '') {
            throw new PaiementIndisponible('Jeton Paddle absent (PADDLE_CLIENT_SIDE_TOKEN).');
        }

        // Données rappelées par les webhooks : type d'abonnement (Cashier) et aperçu à relancer.
        $donnees = ['formule' => $formule];

        if ($formule !== 'pack') {
            $donnees['subscription_type'] = (string) config('voxia.paiement.type_abonnement_pro');
        }

        if ($transcriptionId !== null) {
            $donnees['transcription_id'] = $transcriptionId;
        }

        $options = $utilisateur->checkout($prix)
            ->customData($donnees)
            // Après paiement : l'écran d'origine (fenêtre d'upgrade), sinon l'espace Abonnement.
            ->returnTo($retour ?? route('abonnement'))
            ->options();

        return new Ouverture(
            self::FOURNISSEUR,
            config('cashier.sandbox') ? 'sandbox' : 'production',
            (string) config('cashier.client_side_token'),
            $options,
        );
    }

    public function changerFormule(User $utilisateur, string $formule): void
    {
        $abonnement = $this->abonnementPro($utilisateur);

        // Prorata immédiat : le nouveau tarif prend effet tout de suite, l'écart est facturé au prorata.
        $abonnement->prorateImmediately()->swap(self::prixDe($formule));
    }

    public function resilier(User $utilisateur): void
    {
        // Sans « immediately » : Paddle résilie à la fin de la période payée.
        $this->abonnementPro($utilisateur)->cancel();
    }

    public function annulerResiliation(User $utilisateur): void
    {
        $this->abonnementPro($utilisateur)->stopCancelation();
    }

    public function factures(User $utilisateur): array
    {
        return $utilisateur->transactions()->get()->map(fn ($transaction) => [
            'id' => (string) $transaction->paddle_id,
            'numero' => $transaction->invoice_number,
            'date' => $transaction->billed_at,
            'montant' => (string) $transaction->total(),
            'statut' => (string) $transaction->status,
        ])->values()->all();
    }

    public function urlFacture(User $utilisateur, string $idFacture): ?string
    {
        $transaction = $utilisateur->transactions()->where('paddle_id', $idFacture)->first();

        return $transaction?->invoicePdf();
    }

    /**
     * Identifiant de prix Paddle d'une formule, lu dans la config.
     *
     * @throws PaiementIndisponible
     */
    public static function prixDe(string $formule): string
    {
        $prix = config("voxia.paiement.prix.{$formule}");

        if (! is_string($prix) || $prix === '') {
            throw new PaiementIndisponible("Prix Paddle non configuré pour « {$formule} ».");
        }

        return $prix;
    }

    /** Abonnement Pro valide (actif, ou résilié mais encore dans la période payée). */
    private function abonnementPro(User $utilisateur)
    {
        $abonnement = $utilisateur->subscription((string) config('voxia.paiement.type_abonnement_pro'));

        if ($abonnement === null || ! $abonnement->valid()) {
            throw new PaiementIndisponible('Aucun abonnement Pro en cours.');
        }

        return $abonnement;
    }
}
