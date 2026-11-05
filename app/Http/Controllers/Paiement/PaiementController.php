<?php

namespace App\Http\Controllers\Paiement;

use App\Contracts\BillingProvider;
use App\Exceptions\PaiementIndisponible;
use App\Http\Controllers\Controller;
use App\Models\Transcription;
use App\Support\CheminLocal;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Page de paiement d'une formule. Le paiement lui-même se fait dans l'overlay Paddle (côté navigateur) ;
 * le serveur ne fait que préparer l'ouverture. Un aperçu partiel (?transcription=) est rappelé au
 * prestataire pour être relancé en version complète après paiement.
 */
class PaiementController extends Controller
{
    /** Formules vendables, et leur montant affiché (config des plans, en dollars US). */
    private const FORMULES = [
        'pro_mensuel' => 'voxia.plans.pro.price_monthly_usd',
        'pro_annuel' => 'voxia.plans.pro.price_yearly_usd',
        'business_mensuel' => 'voxia.plans.business.price_monthly_usd',
        'business_annuel' => 'voxia.plans.business.price_yearly_usd',
        'pack' => 'voxia.plans.pack.price_usd',
    ];

    public function afficher(Request $request, string $produit, BillingProvider $facturation): View|RedirectResponse
    {
        abort_unless(array_key_exists($produit, self::FORMULES), 404);

        $utilisateur = $request->user();

        // Un seul abonnement par compte : une formule déjà souscrise se change depuis l'espace Abonnement.
        if ($produit !== 'pack' && $utilisateur->subscribed((string) config('voxia.paiement.type_abonnement_pro'))) {
            return redirect()->route('abonnement')->with('statut', str_starts_with($produit, 'business_') ? 'deja-abonne' : 'deja-pro');
        }

        $apercu = $this->apercuDuCompte($request);

        // Écran d'origine (fenêtre d'upgrade) : seulement une adresse de cette application.
        $retour = CheminLocal::valider($request->query('retour'));

        try {
            $ouverture = $facturation->ouvrirPaiement($utilisateur, $produit, $apercu?->getKey(), $retour);
        } catch (PaiementIndisponible $erreur) {
            Log::error('paiement: ouverture impossible', ['formule' => $produit, 'erreur' => $erreur->getMessage()]);

            return redirect()->route('abonnement')->with('statut', 'paiement-indisponible');
        }

        return view('app.paiement', [
            'produit' => $produit,
            'valeurs' => [
                'montant' => (float) config(self::FORMULES[$produit]),
                'heures' => (int) config('voxia.plans.pack.hours'),
                'mois' => (int) config('voxia.plans.pack.validity_months'),
                'mensuel' => round((float) config(str_starts_with($produit, 'business_') ? 'voxia.plans.business.price_yearly_usd' : 'voxia.plans.pro.price_yearly_usd') / 12, 2),
            ],
            'ouverture' => $ouverture->versTableau(),
            'apercu' => $apercu,
        ]);
    }

    /** Aperçu partiel du compte passé en paramètre, s'il existe et lui appartient. */
    private function apercuDuCompte(Request $request): ?Transcription
    {
        if (! $request->filled('transcription')) {
            return null;
        }

        return Transcription::query()
            ->where('user_id', $request->user()->getKey())
            ->where('est_apercu', true)
            ->whereKey((int) $request->query('transcription'))
            ->first();
    }
}
