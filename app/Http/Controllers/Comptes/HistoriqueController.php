<?php

namespace App\Http\Controllers\Comptes;

use App\Http\Controllers\Controller;
use App\Services\Plans\PlanFeatures;
use App\Services\Recherche\RechercheTranscriptions;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Historique des transcriptions du compte (cahier F5) : liste triée par date, paginée, et
 * recherche plein texte réservée au Pro et au Pack. En Gratuit, le champ reste visible et
 * propose de passer au Pro ; aucune recherche ne part.
 */
class HistoriqueController extends Controller
{
    public function __invoke(Request $request, PlanFeatures $plans, RechercheTranscriptions $recherche): View
    {
        $utilisateur = $request->user();
        $parPage = (int) config('voxia.historique.par_page');
        $tri = $request->query('tri') === 'ancien' ? 'ancien' : 'recent';
        $ordre = $tri === 'ancien' ? 'asc' : 'desc';
        $terme = trim((string) $request->query('q', ''));
        $peutRechercher = $plans->peutRechercher($utilisateur);

        // Une recherche remplace la liste : une seule pagination, sur le paramètre « page ».
        $resultats = null;
        $transcriptions = null;

        if ($terme !== '' && $peutRechercher) {
            $resultats = $recherche->rechercher($utilisateur, $terme, $parPage, $request->integer('page', 1) ?: 1)
                ->withQueryString();
        } else {
            $transcriptions = $utilisateur->transcriptions()
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
        ]);
    }
}
