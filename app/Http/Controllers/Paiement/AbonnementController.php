<?php

namespace App\Http\Controllers\Paiement;

use App\Contracts\BillingProvider;
use App\Exceptions\PaiementIndisponible;
use App\Http\Controllers\Controller;
use App\Models\PackAchat;
use App\Services\Quota\QuotaService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Espace Abonnement du compte : formule, passage mensuel ↔ annuel, résiliation (accès jusqu'à la fin
 * de la période), factures, Packs achetés et leur expiration. Tout passe par BillingProvider ; l'état
 * affiché vient de la base locale, mise à jour par les webhooks.
 */
class AbonnementController extends Controller
{
    public function afficher(Request $request, BillingProvider $facturation, QuotaService $quotas): View
    {
        $utilisateur = $request->user();
        $abonnement = $utilisateur->subscription($this->typeAbonnement());
        $pro = $utilisateur->subscribed($this->typeAbonnement());
        $formule = $pro ? $this->formuleDe($abonnement?->items()->value('price_id')) : null;

        return view('app.abonnement', [
            'pro' => $pro,
            'formule' => $formule,
            'resiliee' => $pro && $abonnement?->onGracePeriod(),
            'finAcces' => $pro && $abonnement?->onGracePeriod() ? $abonnement->ends_at : null,
            'impayee' => $abonnement?->pastDue() === true,
            'prochaineFacturation' => $pro && ! $abonnement?->onGracePeriod()
                ? $quotas->debutCycleAnniversaire($utilisateur, now())->addMonthNoOverflow()
                : null,
            'packs' => PackAchat::query()
                ->where('user_id', $utilisateur->getKey())
                ->where('expire_at', '>', now())
                ->orderBy('expire_at')
                ->get(),
            'factures' => $facturation->factures($utilisateur),
            'plan' => $utilisateur->plan,
            'quotas' => $quotas->resume($utilisateur),
        ]);
    }

    public function changerFormule(Request $request, BillingProvider $facturation): RedirectResponse
    {
        $donnees = $request->validate([
            'formule' => ['required', 'in:pro_mensuel,pro_annuel'],
        ]);

        $utilisateur = $request->user();

        if (! $utilisateur->subscribed($this->typeAbonnement())) {
            return redirect()->route('abonnement')->with('statut', 'pas-d-abonnement');
        }

        $actuelle = $this->formuleDe($utilisateur->subscription($this->typeAbonnement())?->items()->value('price_id'));

        if ($actuelle === $donnees['formule']) {
            return redirect()->route('abonnement');
        }

        try {
            $facturation->changerFormule($utilisateur, $donnees['formule']);
        } catch (PaiementIndisponible $erreur) {
            Log::error('abonnement: changement de formule impossible', ['erreur' => $erreur->getMessage()]);

            return redirect()->route('abonnement')->with('statut', 'paiement-indisponible');
        }

        return redirect()->route('abonnement')->with('statut', 'formule-changee');
    }

    public function resilier(Request $request, BillingProvider $facturation): RedirectResponse
    {
        $utilisateur = $request->user();

        if (! $utilisateur->subscribed($this->typeAbonnement())) {
            return redirect()->route('abonnement')->with('statut', 'pas-d-abonnement');
        }

        $facturation->resilier($utilisateur);

        return redirect()->route('abonnement')->with('statut', 'resiliee');
    }

    public function annulerResiliation(Request $request, BillingProvider $facturation): RedirectResponse
    {
        $facturation->annulerResiliation($request->user());

        return redirect()->route('abonnement')->with('statut', 'resiliation-annulee');
    }

    public function facture(Request $request, string $facture, BillingProvider $facturation): RedirectResponse
    {
        $url = $facturation->urlFacture($request->user(), $facture);

        abort_if($url === null, 404);

        return redirect()->away($url);
    }

    private function typeAbonnement(): string
    {
        return (string) config('voxia.paiement.type_abonnement_pro');
    }

    /** Formule Pro d'un identifiant de prix Paddle, ou null si ce prix n'est pas une formule Pro. */
    private function formuleDe(?string $prix): ?string
    {
        if ($prix === null || $prix === '') {
            return null;
        }

        return collect(['pro_mensuel', 'pro_annuel'])
            ->first(fn (string $formule) => config("voxia.paiement.prix.{$formule}") === $prix);
    }
}
