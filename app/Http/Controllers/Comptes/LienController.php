<?php

namespace App\Http\Controllers\Comptes;

use App\Contracts\VideoFetcher;
use App\Exceptions\ErreurVideo;
use App\Http\Controllers\Controller;
use App\Services\Comptes\LienEnAttente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Lien collé sur la page d'accueil. Un visiteur est envoyé à l'inscription, avec le lien
 * gardé en session ; un utilisateur connecté le reçoit sur son compte. Rien n'est lancé ici :
 * le lancement a lieu à l'arrivée dans l'application, email vérifié (cahier §2, étape 2).
 */
class LienController extends Controller
{
    public function __construct(private readonly VideoFetcher $videos) {}

    public function store(Request $request): RedirectResponse
    {
        $donnees = $request->validate([
            'lien' => ['required', 'string', 'max:2048', 'url:http,https'],
        ], [
            'lien.required' => __('comptes.lien.vide'),
            'lien.max' => __('comptes.lien.invalide'),
            'lien.url' => __('comptes.lien.invalide'),
        ]);

        $lien = trim($donnees['lien']);

        try {
            $this->videos->reconnaitre($lien);
        } catch (ErreurVideo) {
            return back()->withInput()->withErrors(['lien' => __('comptes.lien.invalide')]);
        }

        if ($utilisateur = $request->user()) {
            $utilisateur->forceFill(['lien_en_attente' => $lien])->save();

            return redirect()->route('tableau-de-bord');
        }

        LienEnAttente::memoriserEnSession($request, $lien);

        return redirect()->route('register');
    }
}
