<?php

namespace App\Http\Controllers\Comptes;

use App\Http\Controllers\Controller;
use App\Services\Televersement\Televersement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Réception d'un morceau d'un fichier importé. Le navigateur envoie les morceaux un par un
 * (voir resources/js/televersement.js). Le contrôle du type et de la taille se fait ici,
 * avant toute écriture ; le contrôle du contenu (format audio) se fait à l'extraction.
 */
class TeleversementController extends Controller
{
    public function morceau(Request $request, Televersement $televersement): JsonResponse
    {
        $taillePermise = Televersement::tailleMaxOctets();
        $morceauOctets = (int) config('voxia.uploads.morceau_octets');

        $donnees = $request->validate([
            'envoi' => ['required', 'string', 'size:36'],
            'nom' => ['required', 'string', 'max:255'],
            'taille' => ['required', 'integer', 'min:1', 'max:'.$taillePermise],
            'index' => ['required', 'integer', 'min:0'],
            'total' => ['required', 'integer', 'min:1'],
            'morceau' => ['required', 'file', 'max:'.(int) ceil($morceauOctets / 1024)],
        ], [
            'taille.max' => __('transcriptions.televersement.trop_gros', ['max' => config('voxia.uploads.max_size_mb')]),
            'taille.min' => __('transcriptions.televersement.vide'),
        ]);

        if (! Televersement::estIdentifiantEnvoi($donnees['envoi'])) {
            return response()->json(['message' => __('transcriptions.televersement.echec')], 422);
        }

        if (! Televersement::extensionAcceptee($donnees['nom'])) {
            return response()->json(['message' => __('transcriptions.televersement.format')], 422);
        }

        // Les champs de formulaire arrivent en chaînes : on les convertit avant toute comparaison.
        $index = (int) $donnees['index'];
        $total = (int) $donnees['total'];
        $taille = (int) $donnees['taille'];

        if ($total !== Televersement::nombreMorceaux($taille) || $index >= $total) {
            return response()->json(['message' => __('transcriptions.televersement.decoupage')], 422);
        }

        try {
            $nom = $televersement->recevoirMorceau(
                $request->user(),
                $donnees['envoi'],
                $index,
                $total,
                $taille,
                $donnees['nom'],
                $request->file('morceau'),
            );
        } catch (RuntimeException) {
            return response()->json(['message' => __('transcriptions.televersement.echec')], 422);
        }

        return response()->json(['termine' => $nom !== null, 'nom' => $nom]);
    }
}
