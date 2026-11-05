<?php

namespace App\Http\Controllers\Comptes;

use App\Http\Controllers\Controller;
use App\Jobs\ExporterDonneesCompte;
use App\Jobs\SupprimerFichiersTranscriptions;
use App\Mail\CompteSupprime;
use App\Models\User;
use App\Services\Plans\PlanFeatures;
use App\Services\Quota\QuotaService;
use App\Support\Duree;
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
    public function afficher(Request $request): View
    {
        $utilisateur = $request->user();

        return view('app.reglages', [
            'utilisateur' => $utilisateur,
            'langues' => Langue::disponibles(),
            'exportDisponible' => Storage::disk((string) config('voxia.stockage.disque'))
                ->exists(ExporterDonneesCompte::chemin($utilisateur->getKey())),
            'formule' => $this->formule($utilisateur),
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
        $resume = app(QuotaService::class)->resume($utilisateur);
        $quotas = [];

        if (isset($resume['videos_limite'])) {
            $quotas[] = [
                'libelle' => __('comptes.reglages.formule.videos'),
                'valeur' => $resume['videos_utilisees'].' / '.$resume['videos_limite'],
                'pourcentage' => $resume['videos_limite'] > 0
                    ? min(100, (int) round($resume['videos_utilisees'] / $resume['videos_limite'] * 100))
                    : null,
            ];
        } else {
            $secondesMois = $resume['secondes_mois'] ?? null;
            $secondesPacks = (int) ($resume['secondes_packs'] ?? 0);

            if ($secondesMois !== null) {
                $quotas[] = ['libelle' => __('comptes.reglages.formule.heures_mois'), 'valeur' => Duree::formater($secondesMois), 'pourcentage' => null];
            }

            if ($secondesMois === null || $secondesPacks > 0) {
                $quotas[] = ['libelle' => __('comptes.reglages.formule.heures_packs'), 'valeur' => Duree::formater($secondesPacks), 'pourcentage' => null];
            }
        }

        $creditsIa = $resume['credits_ia'] ?? null;

        if ($creditsIa !== null) {
            $quotas[] = ['libelle' => __('comptes.reglages.formule.credits_ia'), 'valeur' => (string) $creditsIa, 'pourcentage' => null];
        }

        $cle = in_array($resume['plan']->value, ['free', 'pro', 'pack'], true) ? $resume['plan']->value : 'free';

        return [
            'nom' => $droits->libellePlan($resume['plan']),
            'detail' => __('comptes.reglages.formule.detail.'.$cle),
            'quotas' => $quotas,
        ];
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
