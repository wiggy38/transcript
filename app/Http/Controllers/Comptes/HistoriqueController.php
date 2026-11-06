<?php

namespace App\Http\Controllers\Comptes;

use App\Enums\TypeEtiquette;
use App\Http\Controllers\Controller;
use App\Models\Transcription;
use App\Services\Bibliotheque\FiltresBibliotheque;
use App\Services\Bibliotheque\NavigationBibliotheque;
use App\Services\Plans\PlanFeatures;
use App\Services\Recherche\RechercheTranscriptions;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Historique des transcriptions du compte (cahier F5) : liste triée par date, paginée, et
 * recherche plein texte réservée au Pro et au Pack. En Gratuit, le champ reste visible et
 * propose de passer au Pro ; aucune recherche ne part.
 *
 * Bibliothèque (V2) : pour le Pro et le Business, et pour un compte qui garde des dossiers ou
 * des étiquettes en lecture seule, la liste se filtre et la colonne latérale apparaît. Les
 * autres plans gardent la liste du MVP : les filtres de l'URL sont alors ignorés.
 */
class HistoriqueController extends Controller
{
    public function __invoke(
        Request $request,
        PlanFeatures $plans,
        RechercheTranscriptions $recherche,
        FiltresBibliotheque $filtres,
        NavigationBibliotheque $navigation,
    ): View {
        $utilisateur = $request->user();
        $parPage = (int) config('voxia.historique.par_page');
        $tri = $request->query('tri') === 'ancien' ? 'ancien' : 'recent';
        $ordre = $tri === 'ancien' ? 'asc' : 'desc';
        $terme = trim((string) $request->query('q', ''));
        $peutRechercher = $plans->peutRechercher($utilisateur);
        $peutClasser = $plans->peutClasser($utilisateur);

        $bibliotheque = $peutClasser || $utilisateur->dossiers()->exists() || $utilisateur->etiquettes()->exists();
        $filtre = $bibliotheque ? $filtres->depuisRequete($request) : $filtres->vides();

        $requete = $filtres->appliquer(
            Transcription::query()->where('user_id', $utilisateur->getKey()),
            $filtre,
        );

        // Une recherche remplace la liste : une seule pagination, sur le paramètre « page ».
        $resultats = null;
        $transcriptions = null;

        if ($terme !== '' && $peutRechercher) {
            // La portée « vue filtrée » ne s'applique que si un filtre restreint réellement la liste.
            $portee = $filtre['portee'] === FiltresBibliotheque::PORTEE_FILTRE && $filtres->estRestreinte($filtre)
                ? (clone $requete)
                : null;

            $resultats = $recherche->rechercher($utilisateur, $terme, $parPage, $request->integer('page', 1) ?: 1, $portee)
                ->withQueryString();
        } else {
            $transcriptions = (clone $requete)
                ->with(['dossier:id,nom', 'etiquettes:id,type,nom'])
                ->orderBy('created_at', $ordre)
                ->orderBy('id', $ordre)
                ->paginate($parPage)
                ->withQueryString();
        }

        return view('app.historique', [
            'transcriptions' => $transcriptions,
            'resultats' => $resultats,
            'terme' => $terme,
            'tri' => $tri,
            'peutRechercher' => $peutRechercher,
            'joursConservation' => $plans->joursConservation($utilisateur),
            'avecRecherche' => $terme !== '',
            'bibliotheque' => $bibliotheque,
            'peutClasser' => $peutClasser,
            'filtre' => $filtre,
            'filtresActifs' => $filtres->estRestreinte($filtre),
            'navigation' => $bibliotheque ? $navigation->pour($utilisateur) : null,
            'etiquettesTypes' => TypeEtiquette::cases(),
        ]);
    }
}
