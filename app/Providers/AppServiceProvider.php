<?php

namespace App\Providers;

use App\Contracts\AudioExtractor;
use App\Contracts\BillingProvider;
use App\Contracts\ScriptAnalyzer;
use App\Contracts\TranscriptionEngine;
use App\Contracts\VideoFetcher;
use App\Models\User;
use App\Services\Analyse\ClaudeScriptAnalyzer;
use App\Services\Audio\FfmpegAudioExtractor;
use App\Services\Comptes\LienEnAttente;
use App\Services\Paiement\PaddleBillingProvider;
use App\Services\Transcription\ElevenLabsScribeEngine;
use App\Services\Video\YtDlpVideoFetcher;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Laravel\Paddle\Cashier;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(VideoFetcher::class, YtDlpVideoFetcher::class);
        $this->app->bind(AudioExtractor::class, FfmpegAudioExtractor::class);
        $this->app->bind(TranscriptionEngine::class, ElevenLabsScribeEngine::class);
        $this->app->bind(ScriptAnalyzer::class, ClaudeScriptAnalyzer::class);
        $this->app->bind(BillingProvider::class, PaddleBillingProvider::class);

        // Les webhooks sont déclarés dans routes/web.php (réception vérifiée et mise en file).
        Cashier::ignoreRoutes();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Mises en page : <x-layouts::app> et <x-layouts::marketing> (resources/views/layouts).
        Blade::anonymousComponentPath(resource_path('views/layouts'), 'layouts');

        // Emails transactionnels au style Signal (resources/views/emails, thème « signal »).
        VerifyEmail::toMailUsing(fn (object $notifiable, string $url) => (new MailMessage)
            ->subject(__('courriels.verification.objet'))
            ->markdown('emails.verification', ['url' => $url, 'nom' => $notifiable->name]));

        ResetPassword::toMailUsing(fn (object $notifiable, string $token) => (new MailMessage)
            ->subject(__('courriels.reinitialisation.objet'))
            ->markdown('emails.reinitialisation', [
                'url' => url(route('password.reset', ['token' => $token, 'email' => $notifiable->getEmailForPasswordReset()], false)),
                'nom' => $notifiable->name,
            ]));

        // Un lien collé avant la connexion suit le visiteur jusqu'à son compte.
        Event::listen(Login::class, function (Login $evenement) {
            if (request()->hasSession() && $evenement->user instanceof User) {
                LienEnAttente::reporterVersCompte(request(), $evenement->user);
            }
        });
    }
}
