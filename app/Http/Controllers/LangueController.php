<?php

namespace App\Http\Controllers;

use App\Support\CheminLocal;
use App\Support\Langue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Sélecteur de langue (en-tête et réglages). Mémorise le choix dans le navigateur, l'enregistre
 * sur le compte connecté, puis renvoie sur la même page : sur une page publique, dans l'adresse
 * de l'autre langue (/fr → /en).
 */
class LangueController extends Controller
{
    public function changer(Request $request): RedirectResponse
    {
        $donnees = $request->validate([
            'locale' => ['required', Rule::in(Langue::disponibles())],
            'retour' => ['nullable', 'string', 'max:2048'],
        ]);

        $langue = $donnees['locale'];

        if ($utilisateur = $request->user()) {
            $utilisateur->forceFill(['locale' => $langue])->save();
        }

        // Retour limité à un chemin de l'application : un lien externe n'est jamais suivi.
        $cible = CheminLocal::valider($donnees['retour'] ?? null);

        if ($cible === null) {
            $cible = route('accueil', ['locale' => $langue]);
        } else {
            $cible = url(Langue::remplacerPrefixe(CheminLocal::depuisUrl($cible), $langue));
        }

        return redirect($cible)->withCookie(Langue::cookie($langue));
    }
}
