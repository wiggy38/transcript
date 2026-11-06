<?php

namespace App\Http\Controllers\Comptes;

use App\Enums\TypeEtiquette;
use App\Http\Controllers\Controller;
use App\Models\Dossier;
use App\Models\Etiquette;
use App\Models\Transcription;
use App\Services\Bibliotheque\ClassementTranscriptions;
use App\Services\Bibliotheque\FiltresBibliotheque;
use App\Services\Bibliotheque\GestionBibliotheque;
use App\Services\Bibliotheque\NavigationBibliotheque;
use App\Services\Historique\SuppressionTranscription;
use App\Services\Plans\PlanFeatures;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Bibliothèque classée (V2) : dossiers, étiquettes, classement d'une transcription et actions groupées.
 *
 * Chaque écriture passe par la Policy du modèle (plan Pro ou Business) et vérifie que la ressource
 * appartient au compte : un identifiant d'un autre compte répond 404, comme dans le reste de l'application.
 */
class BibliothequeController extends Controller
{
    public function gerer(Request $request, NavigationBibliotheque $navigation, PlanFeatures $plans): View
    {
        $utilisateur = $request->user();

        return view('app.bibliotheque', [
            'navigation' => $navigation->pour($utilisateur),
            'peutClasser' => $plans->peutClasser($utilisateur),
            'types' => TypeEtiquette::cases(),
        ]);
    }

    public function storeDossier(Request $request, GestionBibliotheque $gestion): RedirectResponse
    {
        Gate::authorize('create', Dossier::class);

        $donnees = $request->validate(['nom' => ['required', 'string', 'max:80']]);

        $gestion->creerDossier($request->user(), trim($donnees['nom']));

        return redirect()->route('bibliotheque.gerer')->with('statut', 'dossier-cree');
    }

    public function updateDossier(Request $request, Dossier $dossier, GestionBibliotheque $gestion): RedirectResponse
    {
        abort_unless($dossier->user_id === $request->user()->getKey(), 404);
        Gate::authorize('update', $dossier);

        $donnees = $request->validate(['nom' => ['required', 'string', 'max:80']]);

        $gestion->renommerDossier($dossier, trim($donnees['nom']));

        return redirect()->route('bibliotheque.gerer')->with('statut', 'dossier-renomme');
    }

    public function destroyDossier(Request $request, Dossier $dossier, GestionBibliotheque $gestion): RedirectResponse
    {
        abort_unless($dossier->user_id === $request->user()->getKey(), 404);
        Gate::authorize('delete', $dossier);

        $gestion->supprimerDossier($dossier);

        return redirect()->route('bibliotheque.gerer')->with('statut', 'dossier-supprime');
    }

    public function storeEtiquette(Request $request, GestionBibliotheque $gestion): RedirectResponse
    {
        Gate::authorize('create', Etiquette::class);

        $donnees = $request->validate([
            'nom' => ['required', 'string', 'max:80'],
            'type' => ['required', Rule::enum(TypeEtiquette::class)],
        ]);

        $gestion->trouverOuCreerEtiquette($request->user(), TypeEtiquette::from($donnees['type']), trim($donnees['nom']));

        return redirect()->route('bibliotheque.gerer')->with('statut', 'etiquette-creee');
    }

    public function updateEtiquette(Request $request, Etiquette $etiquette, GestionBibliotheque $gestion): RedirectResponse
    {
        abort_unless($etiquette->user_id === $request->user()->getKey(), 404);
        Gate::authorize('update', $etiquette);

        $donnees = $request->validate([
            'nom' => ['required', 'string', 'max:80'],
            'type' => ['required', Rule::enum(TypeEtiquette::class)],
        ]);

        $gestion->renommerEtiquette($etiquette, TypeEtiquette::from($donnees['type']), trim($donnees['nom']));

        return redirect()->route('bibliotheque.gerer')->with('statut', 'etiquette-renommee');
    }

    public function destroyEtiquette(Request $request, Etiquette $etiquette, GestionBibliotheque $gestion): RedirectResponse
    {
        abort_unless($etiquette->user_id === $request->user()->getKey(), 404);
        Gate::authorize('delete', $etiquette);

        $gestion->supprimerEtiquette($etiquette);

        return redirect()->route('bibliotheque.gerer')->with('statut', 'etiquette-supprimee');
    }

    /** Page de classement d'une transcription : dossier et étiquettes, avec une étiquette nouvelle possible. */
    public function classement(Request $request, Transcription $transcription, NavigationBibliotheque $navigation): View
    {
        abort_unless($transcription->user_id === $request->user()->getKey(), 404);
        Gate::authorize('classer', $transcription);

        $utilisateur = $request->user();

        return view('app.classement', [
            'transcription' => $transcription,
            'dossiers' => $utilisateur->dossiers()->orderBy('nom_normalise')->get(),
            'etiquettes' => $navigation->pour($utilisateur)['etiquettes'],
            'etiquettesChoisies' => $transcription->etiquettes()->pluck('etiquettes.id')->all(),
            'nomsConnus' => $utilisateur->etiquettes()->orderBy('nom')->pluck('nom')->unique()->values(),
            'types' => TypeEtiquette::cases(),
        ]);
    }

    public function enregistrerClassement(Request $request, Transcription $transcription, GestionBibliotheque $gestion, ClassementTranscriptions $classement): RedirectResponse
    {
        abort_unless($transcription->user_id === $request->user()->getKey(), 404);
        Gate::authorize('classer', $transcription);

        $utilisateur = $request->user();

        $donnees = $request->validate([
            'dossier_id' => ['nullable', Rule::exists('dossiers', 'id')->where('user_id', $utilisateur->getKey())],
            'etiquettes' => ['nullable', 'array'],
            'etiquettes.*' => ['integer', Rule::exists('etiquettes', 'id')->where('user_id', $utilisateur->getKey())],
            'nouvelle_etiquette' => ['nullable', 'string', 'max:80'],
            'nouvelle_etiquette_type' => ['nullable', Rule::enum(TypeEtiquette::class)],
        ]);

        $dossier = isset($donnees['dossier_id']) ? Dossier::find($donnees['dossier_id']) : null;
        $classement->ranger($utilisateur, [$transcription->getKey()], $dossier);

        $etiquettes = array_map('intval', $donnees['etiquettes'] ?? []);

        if (trim((string) ($donnees['nouvelle_etiquette'] ?? '')) !== '') {
            $type = TypeEtiquette::tryFrom((string) ($donnees['nouvelle_etiquette_type'] ?? '')) ?? TypeEtiquette::Libre;
            $etiquettes[] = $gestion->trouverOuCreerEtiquette($utilisateur, $type, trim($donnees['nouvelle_etiquette']))->getKey();
        }

        $classement->definirEtiquettes($transcription, $etiquettes);

        return redirect()->route('transcriptions.classement', $transcription)->with('statut', 'classement-enregistre');
    }

    /**
     * Actions groupées sur la liste : déplacer, étiqueter ou supprimer les transcriptions cochées.
     * Déplacer et étiqueter demandent le plan ; supprimer reste ouvert à tous (la suppression n'est pas un classement).
     */
    public function selection(Request $request, ClassementTranscriptions $classement, SuppressionTranscription $suppression, PlanFeatures $plans): RedirectResponse
    {
        $utilisateur = $request->user();

        $donnees = $request->validate([
            'action' => ['required', Rule::in(['deplacer', 'etiqueter', 'supprimer'])],
            'selection' => ['required', 'array', 'min:1'],
            'selection.*' => ['integer'],
            'dossier_id' => ['required_if:action,deplacer', 'nullable', 'string'],
            'etiquettes' => ['required_if:action,etiqueter', 'nullable', 'array'],
            'etiquettes.*' => ['nullable', 'integer'],
        ]);

        // Seules les transcriptions du compte sont touchées, quelle que soit la liste envoyée.
        $identifiants = Transcription::query()
            ->where('user_id', $utilisateur->getKey())
            ->whereIn('id', array_map('intval', $donnees['selection']))
            ->pluck('id')
            ->map(fn ($identifiant) => (int) $identifiant)
            ->all();

        return match ($donnees['action']) {
            'deplacer' => $this->deplacer($utilisateur, $identifiants, $donnees['dossier_id'] ?? '', $classement, $plans),
            'etiqueter' => $this->etiqueter($utilisateur, $identifiants, array_filter(array_map('intval', $donnees['etiquettes'] ?? [])), $classement, $plans),
            default => $this->supprimer($identifiants, $suppression),
        };
    }

    /** @param  list<int>  $identifiants */
    private function deplacer($utilisateur, array $identifiants, string $dossierId, ClassementTranscriptions $classement, PlanFeatures $plans): RedirectResponse
    {
        abort_unless($plans->peutClasser($utilisateur), 403);

        if ($dossierId === FiltresBibliotheque::SANS_DOSSIER) {
            $dossier = null;
        } else {
            $dossier = ctype_digit($dossierId) ? $utilisateur->dossiers()->find((int) $dossierId) : null;
            abort_if($dossier === null, 422);
        }

        $classement->ranger($utilisateur, $identifiants, $dossier);

        return back(fallback: route('historique'))->with('statut', 'selection-deplacee');
    }

    /** @param  list<int>  $identifiants  @param  array<int, int>  $etiquettes */
    private function etiqueter($utilisateur, array $identifiants, array $etiquettes, ClassementTranscriptions $classement, PlanFeatures $plans): RedirectResponse
    {
        abort_unless($plans->peutClasser($utilisateur), 403);

        // Une étiquette doit être choisie : sinon, rien ne serait ajouté et le message de succès mentirait.
        if ($etiquettes === []) {
            throw ValidationException::withMessages(['etiquettes' => __('bibliotheque.selection.aucune_etiquette')]);
        }

        $classement->etiqueter($utilisateur, $identifiants, array_values($etiquettes));

        return back(fallback: route('historique'))->with('statut', 'selection-etiquetee');
    }

    /** @param  list<int>  $identifiants */
    private function supprimer(array $identifiants, SuppressionTranscription $suppression): RedirectResponse
    {
        $suppression->supprimer($identifiants);

        return back(fallback: route('historique'))->with('statut', 'selection-supprimee');
    }
}
