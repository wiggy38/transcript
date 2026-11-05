<?php

namespace App\Http\Controllers\Comptes;

use App\Contracts\BillingProvider;
use App\Enums\FormatExport;
use App\Http\Controllers\Controller;
use App\Jobs\ExporterDonneesCompte;
use App\Jobs\SupprimerFichiersTranscriptions;
use App\Mail\CompteSupprime;
use App\Models\PackAchat;
use App\Models\User;
use App\Services\Plans\PlanFeatures;
use App\Services\Quota\QuotaService;
use App\Support\Duree;
use App\Support\Formats;
use App\Support\Langue;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Réglages du compte : langue, suppression. Nom, email et mot de passe passent par les
 * actions de Fortify (routes user-profile-information.update et user-password.update).
 */
class ReglagesController extends Controller
{
    public function afficher(Request $request, BillingProvider $facturation): View
    {
        $utilisateur = $request->user();

        return view('app.reglages', [
            'utilisateur' => $utilisateur,
            'langues' => Langue::disponibles(),
            'exportDisponible' => Storage::disk((string) config('voxia.stockage.disque'))
                ->exists(ExporterDonneesCompte::chemin($utilisateur->getKey())),
            'formule' => $this->formule($utilisateur),
            'factures' => $facturation->factures($utilisateur),
        ]);
    }

    /**
     * « Ta formule » (maquette « Compte ») : nom du plan, détail et quotas restants, tirés de
     * QuotaService et de la configuration. Aucune valeur de plan n'est écrite ici.
     *
     * @return array{nom: string, detail: string, quotas: list<array{libelle: string, valeur: string, pourcentage: ?int}>}
     */
    private function formule(User $utilisateur): array
    {
        $droits = app(PlanFeatures::class);
        $service = app(QuotaService::class);
        $resume = $service->resume($utilisateur);
        $plan = $resume['plan'];
        $cle = in_array($plan->value, ['free', 'pro', 'pack'], true) ? $plan->value : 'free';

        $formats = $droits->formatsExport($utilisateur);
        $exports = count($formats) === count(FormatExport::cases())
            ? __('comptes.reglages.formule.tous')
            : implode(', ', array_map(fn (FormatExport $format) => strtoupper($format->value), $formats));

        $quotas = match ($cle) {
            'free' => [
                ...(isset($resume['videos_limite']) ? [[
                    'libelle' => __('comptes.reglages.formule.videos'),
                    'valeur' => $resume['videos_utilisees'].' / '.$resume['videos_limite'],
                    'pourcentage' => $resume['videos_limite'] > 0
                        ? min(100, (int) round($resume['videos_utilisees'] / $resume['videos_limite'] * 100))
                        : null,
                ]] : []),
                ['libelle' => __('comptes.reglages.formule.duree_max'), 'valeur' => intdiv($droits->dureeMaxSecondes($plan), 60).' min', 'pourcentage' => null],
                ['libelle' => __('comptes.reglages.formule.exports'), 'valeur' => $exports, 'pourcentage' => null],
            ],
            'pro' => [
                ...(isset($resume['secondes_mois']) ? [[
                    'libelle' => __('comptes.reglages.formule.heures_mois'),
                    'valeur' => Duree::formater($resume['secondes_mois']),
                    'pourcentage' => null,
                ]] : []),
                ['libelle' => __('comptes.reglages.formule.packs'), 'valeur' => Duree::formater((int) ($resume['secondes_packs'] ?? 0)), 'pourcentage' => null],
                ...(isset($resume['credits_ia']) ? [[
                    'libelle' => __('comptes.reglages.formule.credits_ia'),
                    'valeur' => (string) $resume['credits_ia'],
                    'pourcentage' => null,
                ]] : []),
            ],
            'pack' => [
                ['libelle' => __('comptes.reglages.formule.heures_packs'), 'valeur' => Duree::formater((int) ($resume['secondes_packs'] ?? 0)), 'pourcentage' => null],
                ['libelle' => __('comptes.reglages.formule.expiration'), 'valeur' => $this->expirationPack($utilisateur), 'pourcentage' => null],
                ['libelle' => __('comptes.reglages.formule.exports'), 'valeur' => $exports, 'pourcentage' => null],
            ],
        };

        $detail = __('comptes.reglages.formule.detail.'.$cle);

        if ($cle === 'pro') {
            $abonnement = $utilisateur->subscription((string) config('voxia.paiement.type_abonnement_pro'));

            if ($abonnement && ! $abonnement->onGracePeriod()) {
                $prochaine = $service->debutCycleAnniversaire($utilisateur, now())->addMonthNoOverflow();
                $detail = __('comptes.reglages.formule.detail.pro_facturation', ['date' => Formats::date($prochaine)]);
            }
        }

        return [
            'nom' => $droits->libellePlan($plan),
            'detail' => $detail,
            'quotas' => $quotas,
        ];
    }

    /** Date d'expiration du Pack qui expire en premier, ou un tiret s'il n'y en a pas. */
    private function expirationPack(User $utilisateur): string
    {
        $expire = PackAchat::query()
            ->where('user_id', $utilisateur->getKey())
            ->where('expire_at', '>', now())
            ->orderBy('expire_at')
            ->value('expire_at');

        return $expire ? Formats::date($expire) : '—';
    }

    /** « auto » efface la préférence : l'interface suit alors le navigateur (App\Support\Langue). */
    public function langue(Request $request): RedirectResponse
    {
        $donnees = $request->validate([
            'locale' => ['required', 'string', 'in:auto,'.implode(',', Langue::disponibles())],
        ]);

        $langue = $donnees['locale'] === 'auto' ? null : $donnees['locale'];

        $request->user()->forceFill(['locale' => $langue])->save();

        // La page suivante s'affiche déjà dans la nouvelle langue (sinon celle du navigateur).
        app()->setLocale($langue ?? Langue::resoudre($request));

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

        Mail::to($email)->queue(new CompteSupprime($nom, Langue::pour($utilisateur)));

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

        // L'export des données (ZIP) contient les mêmes informations : il part avec le compte.
        Storage::disk((string) config('voxia.stockage.disque'))
            ->deleteDirectory(ExporterDonneesCompte::dossier($utilisateur->getKey()));

        return redirect()->route('accueil')->with('statut', 'compte-supprime');
    }
}
