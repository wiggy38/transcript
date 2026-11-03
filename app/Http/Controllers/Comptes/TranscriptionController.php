<?php

namespace App\Http\Controllers\Comptes;

use App\Exceptions\ErreurVideo;
use App\Http\Controllers\Controller;
use App\Services\Traitement\LancerTranscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * Lancement d'une transcription depuis l'application. Réservé aux comptes dont l'email
 * est vérifié (route « verified »). Seules les adresses http(s) sont acceptées : un chemin
 * de fichier local ne vient jamais du web.
 */
class TranscriptionController extends Controller
{
    public function store(Request $request, LancerTranscription $lancement): RedirectResponse
    {
        $donnees = $request->validate([
            'lien' => ['required', 'string', 'max:2048', 'url:http,https'],
            'langue' => ['nullable', 'string', 'max:10'],
        ], [
            'lien.required' => __('comptes.lien.vide'),
            'lien.url' => __('comptes.lien.invalide'),
        ]);

        try {
            $lancement->lancer($request->user(), trim($donnees['lien']), $donnees['langue'] ?? null);
        } catch (ErreurVideo $erreur) {
            return back()->withInput()->withErrors(['lien' => $erreur->messageUtilisateur()]);
        } catch (InvalidArgumentException) {
            return back()->withInput()->withErrors(['lien' => __('comptes.lien.invalide')]);
        }

        return redirect()->route('tableau-de-bord')->with('statut', 'transcription-lancee');
    }
}
