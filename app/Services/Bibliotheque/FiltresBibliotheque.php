<?php

namespace App\Services\Bibliotheque;

use App\Enums\SourceTranscription;
use App\Enums\TypeEtiquette;
use App\Models\Transcription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Filtres de la bibliothèque (V2), lus dans l'URL pour qu'une vue reste en favori.
 *
 * Le formulaire ne propose que trois filtres, qui se combinent (intersection) :
 *   - « etiquettes » : étiquettes de niche, concurrent ou libre (toutes celles choisies doivent figurer) ;
 *   - « produit » : une étiquette de type produit ;
 *   - « plateforme » : YouTube, TikTok ou fichier importé.
 *
 * « dossier » et « chaine » ne sont pas des filtres du formulaire : ce sont les liens de la colonne
 * latérale, et leur valeur reste lisible dans l'URL. « portee » indique si la recherche plein texte
 * porte sur toute la bibliothèque ou sur la vue filtrée.
 */
class FiltresBibliotheque
{
    public const TOUTES = 'toutes';

    public const SANS_DOSSIER = 'aucun';

    public const PORTEE_TOUT = 'tout';

    public const PORTEE_FILTRE = 'filtre';

    /**
     * @return array{dossier: string, etiquettes: list<int>, produit: ?int, plateforme: ?string, chaine: ?string, portee: string}
     */
    public function depuisRequete(Request $requete): array
    {
        $dossier = (string) $requete->query('dossier', self::TOUTES);

        $etiquettes = $requete->query('etiquettes', []);
        $etiquettes = is_array($etiquettes) ? $etiquettes : [];

        $produit = (string) $requete->query('produit', '');
        $plateforme = (string) $requete->query('plateforme', '');
        $chaine = trim((string) $requete->query('chaine', ''));

        return [
            'dossier' => $dossier === self::SANS_DOSSIER || ctype_digit($dossier) ? $dossier : self::TOUTES,
            'etiquettes' => array_values(array_unique(array_filter(
                array_map(fn ($identifiant) => ctype_digit((string) $identifiant) ? (int) $identifiant : 0, $etiquettes),
            ))),
            'produit' => ctype_digit($produit) && (int) $produit > 0 ? (int) $produit : null,
            'plateforme' => in_array($plateforme, array_column(SourceTranscription::cases(), 'value'), true) ? $plateforme : null,
            'chaine' => $chaine === '' ? null : Str::limit($chaine, 255, ''),
            'portee' => $requete->query('portee') === self::PORTEE_FILTRE ? self::PORTEE_FILTRE : self::PORTEE_TOUT,
        ];
    }

    /** @return array{dossier: string, etiquettes: list<int>, produit: ?int, plateforme: ?string, chaine: ?string, portee: string} */
    public function vides(): array
    {
        return [
            'dossier' => self::TOUTES,
            'etiquettes' => [],
            'produit' => null,
            'plateforme' => null,
            'chaine' => null,
            'portee' => self::PORTEE_TOUT,
        ];
    }

    /** Vrai si au moins un filtre ou un lien de navigation restreint la liste (la portée n'en est pas un). */
    public function estRestreinte(array $filtres): bool
    {
        return $filtres['dossier'] !== self::TOUTES
            || $filtres['etiquettes'] !== []
            || $filtres['produit'] !== null
            || $filtres['plateforme'] !== null
            || $filtres['chaine'] !== null;
    }

    /**
     * @param  Builder<Transcription>  $requete
     * @param  array{dossier: string, etiquettes: list<int>, produit: ?int, plateforme: ?string, chaine: ?string, portee: string}  $filtres
     * @return Builder<Transcription>
     */
    public function appliquer(Builder $requete, array $filtres): Builder
    {
        if ($filtres['dossier'] === self::SANS_DOSSIER) {
            $requete->whereNull('dossier_id');
        } elseif (ctype_digit($filtres['dossier'])) {
            $requete->where('dossier_id', (int) $filtres['dossier']);
        }

        foreach ($filtres['etiquettes'] as $etiquette) {
            $requete->whereHas('etiquettes', fn (Builder $etiquettes) => $etiquettes
                ->whereKey($etiquette)
                ->where('type', '!=', TypeEtiquette::Produit->value));
        }

        if ($filtres['produit'] !== null) {
            $requete->whereHas('etiquettes', fn (Builder $etiquettes) => $etiquettes
                ->whereKey($filtres['produit'])
                ->where('type', TypeEtiquette::Produit->value));
        }

        if ($filtres['plateforme'] !== null) {
            $requete->where('source', $filtres['plateforme']);
        }

        if ($filtres['chaine'] !== null) {
            $requete->where('chaine', $filtres['chaine']);
        }

        return $requete;
    }
}
