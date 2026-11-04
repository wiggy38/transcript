<?php

namespace App\Http\Controllers;

use App\Mail\MessageContact;
use App\Support\Langue;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Formulaire de contact des pages publiques. Protections contre le spam, dans cet ordre :
 * 1. limite par adresse IP (voxia.contact.max_par_heure messages par heure) ;
 * 2. champ piège « site_web » : caché aux visiteurs, un robot le remplit. Le message est alors
 *    ignoré sans erreur visible, pour ne pas renseigner le robot ;
 * 3. délai minimal entre l'affichage et l'envoi (voxia.contact.delai_min_secondes), via un jeton chiffré.
 * Le message part en file (MessageContact) : la requête n'attend pas l'envoi.
 */
class ContactController extends Controller
{
    /** Un formulaire ouvert depuis plus de ce délai (en secondes) n'est plus accepté. */
    private const JETON_VALABLE_SECONDES = 86400;

    public function afficher(): View
    {
        return view('pages.contact', [
            'formulaire' => filled(config('voxia.contact.email')),
            'jeton' => Crypt::encryptString((string) now()->getTimestamp()),
        ]);
    }

    public function envoyer(Request $request): RedirectResponse
    {
        $adresse = (string) config('voxia.contact.email');
        abort_if($adresse === '', 404);

        $cleLimite = 'contact:'.$request->ip();

        if (! RateLimiter::attempt($cleLimite, (int) config('voxia.contact.max_par_heure'), fn () => true, 3600)) {
            return back()->withInput()->withErrors(['formulaire' => __('pages.contact.trop_de_messages')]);
        }

        // Champ piège rempli : c'est un robot. On répond comme pour un envoi réussi, sans rien envoyer.
        if (filled($request->input('site_web'))) {
            return redirect()->route('contact')->with('statut', 'envoye');
        }

        if (! $this->delaiRespecte((string) $request->input('ouvert'))) {
            return back()->withInput()->withErrors(['formulaire' => __('pages.contact.trop_rapide')]);
        }

        $donnees = $request->validate([
            'nom' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'sujet' => ['required', 'in:question,bug,facturation,autre'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        Mail::to($adresse)->queue(new MessageContact(
            nom: $donnees['nom'],
            adresse: $donnees['email'],
            sujet: $donnees['sujet'],
            contenu: $donnees['message'],
            langue: Langue::valide(app()->getLocale()) ?? Langue::defaut(),
        ));

        return redirect()->route('contact')->with('statut', 'envoye');
    }

    /** Vrai si le jeton est valide, émis il y a au moins le délai minimal et pas trop ancien. */
    private function delaiRespecte(string $jeton): bool
    {
        try {
            $ouvert = (int) Crypt::decryptString($jeton);
        } catch (DecryptException) {
            return false;
        }

        $ecoule = now()->getTimestamp() - $ouvert;

        return $ecoule >= (int) config('voxia.contact.delai_min_secondes')
            && $ecoule <= self::JETON_VALABLE_SECONDES;
    }
}
