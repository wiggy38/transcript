<?php

namespace App\Http\Controllers\Comptes;

use App\Http\Controllers\Controller;
use App\Services\Comptes\LienEnAttente;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TableauDeBordController extends Controller
{
    /**
     * Arrivée dans l'application. Un lien gardé pendant l'inscription est repris ici dès que
     * l'email est vérifié, puis la page se recharge (PRG) pour afficher le résultat.
     */
    public function __invoke(Request $request): RedirectResponse|View
    {
        $utilisateur = $request->user();

        if ($utilisateur->lien_en_attente !== null && $utilisateur->hasVerifiedEmail()) {
            $transcription = LienEnAttente::reprendre($utilisateur);

            return redirect()
                ->route('tableau-de-bord')
                ->with('statut', $transcription !== null ? 'lien-repris' : 'lien-echec');
        }

        return view('app.tableau-de-bord', [
            'transcriptions' => $utilisateur->transcriptions()->latest()->limit(20)->get(),
            'verifie' => $utilisateur->hasVerifiedEmail(),
        ]);
    }
}
