<?php

namespace App\Services\Bibliotheque;

use App\Enums\TypeEtiquette;
use App\Models\Dossier;
use App\Models\Etiquette;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Données de la colonne latérale de la bibliothèque (V2) : Toutes, Non classé, dossiers, chaînes,
 * étiquettes par type, avec leurs effectifs. Les chaînes sont limitées à voxia.bibliotheque.chaines_affichees.
 */
class NavigationBibliotheque
{
    /**
     * @return array{total: int, sans_dossier: int, dossiers: Collection<int, Dossier>, chaines: Collection<int, object>, etiquettes: array<string, Collection<int, Etiquette>>}
     */
    public function pour(User $utilisateur): array
    {
        $chaines = $utilisateur->transcriptions()
            ->whereNotNull('chaine')
            ->selectRaw('chaine, count(*) as nombre')
            ->groupBy('chaine')
            ->orderByDesc('nombre')
            ->orderBy('chaine')
            ->limit((int) config('voxia.bibliotheque.chaines_affichees'))
            ->get();

        $etiquettes = $utilisateur->etiquettes()
            ->withCount('transcriptions')
            ->orderBy('nom_normalise')
            ->get()
            ->groupBy(fn ($etiquette) => $etiquette->type->value);

        $parType = [];

        foreach (TypeEtiquette::cases() as $type) {
            $parType[$type->value] = $etiquettes->get($type->value, collect());
        }

        return [
            'total' => $utilisateur->transcriptions()->count(),
            'sans_dossier' => $utilisateur->transcriptions()->whereNull('dossier_id')->count(),
            'dossiers' => $utilisateur->dossiers()->withCount('transcriptions')->orderBy('nom_normalise')->get(),
            'chaines' => $chaines,
            'etiquettes' => $parType,
        ];
    }
}
