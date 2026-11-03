<?php

namespace App\Http\Controllers\Comptes;

use App\Http\Controllers\Controller;
use App\Jobs\SupprimerFichiersTranscriptions;
use App\Mail\CompteSupprime;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Réglages du compte : langue, suppression. Nom, email et mot de passe passent par les
 * actions de Fortify (routes user-profile-information.update et user-password.update).
 */
class ReglagesController extends Controller
{
    public function afficher(Request $request): View
    {
        return view('app.reglages', [
            'utilisateur' => $request->user(),
            'langues' => (array) config('voxia.locales'),
        ]);
    }

    public function langue(Request $request): RedirectResponse
    {
        $donnees = $request->validate([
            'locale' => ['required', 'string', 'in:'.implode(',', (array) config('voxia.locales'))],
        ]);

        $request->user()->forceFill(['locale' => $donnees['locale']])->save();

        // La page suivante s'affiche déjà dans la nouvelle langue.
        app()->setLocale($donnees['locale']);

        return redirect()->route('reglages')->with('statut', 'langue-enregistree');
    }

    /**
     * Suppression du compte et de toutes ses données. La confirmation passe par la saisie de
     * l'adresse email, valable pour tous les comptes (y compris ceux créés avec Google).
     * Les lignes sont supprimées tout de suite ; les fichiers MP3 le sont par une tâche de
     * file, pour que la requête n'attende pas le stockage.
     */
    public function supprimer(Request $request): RedirectResponse
    {
        /** @var User $utilisateur */
        $utilisateur = $request->user();

        $donnees = $request->validate([
            'confirmation_email' => ['required', 'string', 'max:255'],
        ]);

        if (Str::lower(trim($donnees['confirmation_email'])) !== Str::lower($utilisateur->email)) {
            throw ValidationException::withMessages([
                'confirmation_email' => __('comptes.suppression.confirmation_incorrecte'),
            ]);
        }

        $email = $utilisateur->email;
        $nom = $utilisateur->name;
        $identifiants = $utilisateur->transcriptions()->pluck('id')->all();

        Mail::to($email)->queue(new CompteSupprime($nom));

        // Déconnexion avant la suppression : Auth::logout() enregistre le compte pour changer
        // son jeton de session, ce qui réinsérerait une ligne supprimée.
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Transaction : tout part ou rien. Les transcriptions partent une à une ; leurs mots,
        // paragraphes, analyses et journaux suivent par cascade de clés étrangères.
        DB::transaction(function () use ($utilisateur) {
            $utilisateur->transcriptions()->get()->each->delete();
            $utilisateur->usageRecords()->delete();
            $utilisateur->delete();
        });

        if ($identifiants !== []) {
            SupprimerFichiersTranscriptions::dispatch($identifiants);
        }

        return redirect()->route('accueil')->with('statut', 'compte-supprime');
    }
}
