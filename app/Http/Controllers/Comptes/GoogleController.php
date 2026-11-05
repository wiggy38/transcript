<?php

namespace App\Http\Controllers\Comptes;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Comptes\LimiteComptesGratuits;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

/**
 * Connexion Google (Socialite). Désactivée par défaut : sans VOXIA_GOOGLE_LOGIN=true, les
 * routes répondent 404. Un compte Google reprend l'email Google, déjà vérifié par Google.
 */
class GoogleController extends Controller
{
    public function __construct(private readonly LimiteComptesGratuits $limite) {}

    public function rediriger(): RedirectResponse
    {
        $this->verifierActive();

        return Socialite::driver('google')->redirect();
    }

    public function retour(Request $request): RedirectResponse
    {
        $this->verifierActive();

        try {
            $google = Socialite::driver('google')->user();
        } catch (Throwable $erreur) {
            Log::warning('Connexion Google interrompue.', ['exception' => $erreur::class]);

            return redirect()->route('login')->withErrors(['google' => __('comptes.google.echec')]);
        }

        $raw = (array) $google->getRaw();

        if (($raw['email_verified'] ?? false) !== true || ! is_string($google->getEmail())) {
            return redirect()->route('login')->withErrors(['google' => __('comptes.google.echec')]);
        }

        $utilisateur = User::query()->where('google_id', $google->getId())->first();

        if ($utilisateur === null) {
            $utilisateur = $this->compteParEmail($google->getEmail(), $google->getId(), $google->getName(), $request);

            if ($utilisateur === null) {
                return redirect()->route('login')->withErrors(['google' => __('comptes.google.echec')]);
            }
        }

        Auth::login($utilisateur);
        $request->session()->regenerate();

        return redirect()->intended(route('tableau-de-bord'));
    }

    /**
     * Compte existant avec le même email, rattaché à Google seulement s'il est vérifié (sinon
     * un compte non vérifié pourrait être pris par un tiers). Sinon, création d'un compte
     * gratuit, soumise à la même limite par IP que l'inscription.
     */
    private function compteParEmail(string $email, string $identifiantGoogle, ?string $nom, Request $request): ?User
    {
        $existant = User::query()->where('email', $email)->first();

        if ($existant !== null) {
            if ($existant->google_id !== null || ! $existant->hasVerifiedEmail()) {
                return null;
            }

            $existant->forceFill(['google_id' => $identifiantGoogle])->save();

            return $existant;
        }

        if ($this->limite->atteinte($request->ip())) {
            return null;
        }

        $utilisateur = new User([
            'name' => $nom ?: $email,
            'email' => $email,
            'locale' => app()->getLocale(),
        ]);

        $utilisateur->forceFill([
            'google_id' => $identifiantGoogle,
            'email_verified_at' => now(),
            'inscription_ip' => $request->ip(),
        ])->save();

        return $utilisateur;
    }

    private function verifierActive(): void
    {
        if (! config('voxia.comptes.google_actif')) {
            throw new HttpException(404);
        }
    }
}
