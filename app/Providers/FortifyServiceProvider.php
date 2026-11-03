<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        // Écrans au style Signal (resources/views/auth).
        Fortify::loginView('auth.connexion');
        Fortify::registerView('auth.inscription');
        Fortify::requestPasswordResetLinkView('auth.mot-de-passe-oublie');
        Fortify::resetPasswordView('auth.reinitialiser');
        Fortify::verifyEmailView('auth.verifier-email');

        // Connexion : 5 tentatives par minute et par couple email + IP (Fortify).
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('inscription', function (Request $request) {
            return Limit::perHour((int) config('voxia.comptes.limites.inscriptions_par_heure_ip'))
                ->by('inscription|'.$request->ip());
        });

        RateLimiter::for('mot-de-passe-oublie', function (Request $request) {
            $cle = Str::transliterate(Str::lower((string) $request->input('email')).'|'.$request->ip());

            return Limit::perHour((int) config('voxia.comptes.limites.mots_de_passe_oublies_par_heure'))
                ->by($cle);
        });

        // Fortify déclare ses routes avant cette méthode : on ajoute le limiteur aux routes
        // d'inscription et de demande de réinitialisation une fois toutes les routes chargées.
        // Recherche par parcours de la liste : la recherche par nom peut être périmée à ce stade.
        $this->app->booted(function () {
            foreach (Route::getRoutes()->getRoutes() as $route) {
                match ($route->getName()) {
                    'register.store' => $route->middleware('throttle:inscription'),
                    'password.email' => $route->middleware('throttle:mot-de-passe-oublie'),
                    default => null,
                };
            }
        });
    }
}
