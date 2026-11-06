<?php

namespace App\Services\Bibliotheque;

use App\Enums\SourceTranscription;
use App\Enums\StatutAnalyseScript;
use App\Enums\StatutSortieIa;
use App\Models\Transcription;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Filtres de la bibliothèque (V2), lus dans l'URL pour qu'une vue reste en favori.
 *
 * Les filtres se combinent (intersection) : dossier, étiquettes (toutes celles choisies),
 * plateforme, chaîne, période, présence d'une analyse de script et d'un résumé terminés.
 * « portee » indique si la recherche plein texte porte sur toute la bibliothèque ou sur la vue filtrée.
 */
class FiltresBibliotheque
{
    public const TOUTES = 'toutes';

    public const SANS_DOSSIER = 'aucun';

    public const PORTEE_TOUT = 'tout';

    public const PORTEE_FILTRE = 'filtre';

    /**
     * @return array{dossier: string, etiquettes: list<int>, plateforme: ?string, chaine: ?string, periode: ?int, analyse: ?string, resume: ?string, portee: string}
     */
    public function depuisRequete(Request $requete): array
    {
        $dossier = (string) $requete->query('dossier', self::TOUTES);

        $etiquettes = $requete->query('etiquettes', []);
        $etiquettes = is_array($etiquettes) ? $etiquettes : [];

        $plateforme = (string) $requete->query('plateforme', '');
        $chaine = trim((string) $requete->query('chaine', ''));
        $periode = (int) $requete->query('periode', 0);

        return [
            'dossier' => $dossier === self::SANS_DOSSIER || ctype_digit($dossier) ? $dossier : self::TOUTES,
            'etiquettes' => array_values(array_unique(array_filter(
                array_map(fn ($identifiant) => ctype_digit((string) $identifiant) ? (int) $identifiant : 0, $etiquettes),
            ))),
            'plateforme' => in_array($plateforme, array_column(SourceTranscription::cases(), 'value'), true) ? $plateforme : null,
            'chaine' => $chaine === '' ? null : Str::limit($chaine, 255, ''),
            'periode' => in_array($periode, (array) config('voxia.bibliotheque.periodes_jours'), true) ? $periode : null,
            'analyse' => $this->ouiNon($requete->query('analyse')),
            'resume' => $this->ouiNon($requete->query('resume')),
            'portee' => $requete->query('portee') === self::PORTEE_FILTRE ? self::PORTEE_FILTRE : self::PORTEE_TOUT,
        ];
    }

    /** @return array{dossier: string, etiquettes: list<int>, plateforme: ?string, chaine: ?string, periode: ?int, analyse: ?string, resume: ?string, portee: string} */
    public function vides(): array
    {
        return [
            'dossier' => self::TOUTES,
            'etiquettes' => [],
            'plateforme' => null,
            'chaine' => null,
            'periode' => null,
            'analyse' => null,
            'resume' => null,
            'portee' => self::PORTEE_TOUT,
        ];
    }

    /** Vrai si au moins un filtre restreint la liste (la portée n'en est pas un). */
    public function estRestreinte(array $filtres): bool
    {
        return $filtres['dossier'] !== self::TOUTES
            || $filtres['etiquettes'] !== []
            || $filtres['plateforme'] !== null
            || $filtres['chaine'] !== null
            || $filtres['periode'] !== null
            || $filtres['analyse'] !== null
            || $filtres['resume'] !== null;
    }

    /**
     * @param  Builder<Transcription>  $requete
     * @param  array{dossier: string, etiquettes: list<int>, plateforme: ?string, chaine: ?string, periode: ?int, analyse: ?string, resume: ?string, portee: string}  $filtres
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
            $requete->whereHas('etiquettes', fn (Builder $etiquettes) => $etiquettes->whereKey($etiquette));
        }

        if ($filtres['plateforme'] !== null) {
            $requete->where('source', $filtres['plateforme']);
        }

        if ($filtres['chaine'] !== null) {
            $requete->where('chaine', $filtres['chaine']);
        }

        if ($filtres['periode'] !== null) {
            $requete->where('created_at', '>=', CarbonImmutable::now()->subDays($filtres['periode']));
        }

        $analyseTerminee = fn (Builder $analyses) => $analyses->where('statut', StatutAnalyseScript::Termine->value);
        $resumeTermine = fn (Builder $sorties) => $sorties
            ->where('fonctionnalite', 'resume')
            ->where('statut', StatutSortieIa::Termine->value);

        match ($filtres['analyse']) {
            'oui' => $requete->whereHas('scriptAnalysis', $analyseTerminee),
            'non' => $requete->whereDoesntHave('scriptAnalysis', $analyseTerminee),
            default => null,
        };

        match ($filtres['resume']) {
            'oui' => $requete->whereHas('aiOutputs', $resumeTermine),
            'non' => $requete->whereDoesntHave('aiOutputs', $resumeTermine),
            default => null,
        };

        return $requete;
    }

    private function ouiNon(mixed $valeur): ?string
    {
        return in_array($valeur, ['oui', 'non'], true) ? $valeur : null;
    }
}
