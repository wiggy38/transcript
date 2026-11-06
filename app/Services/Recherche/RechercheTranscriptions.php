<?php

namespace App\Services\Recherche;

use App\Data\ResultatRecherche;
use App\Enums\StatutSortieIa;
use App\Models\AiOutput;
use App\Models\Transcription;
use App\Models\TranscriptionParagraph;
use App\Models\User;
use App\Support\ConfigurationRecherche;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator as Paginateur;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Recherche plein texte dans toutes les transcriptions d'un compte (cahier F5), et dans leur résumé (V2).
 *
 * Deux temps, pour ne calculer l'extrait que des résultats affichés :
 *   1. les passages trouvés, par langue (un index GIN partiel par configuration) : les paragraphes
 *      (transcription_paragraphs) et les résumés terminés (ai_outputs.texte_recherche), dans un CTE
 *      MATERIALIZED. La recherche dans l'index se fait en premier, quelles que soient les statistiques
 *      de la table. Le CTE joint ensuite les transcriptions du compte, triées par date. Un résumé porte
 *      la position 0 : il apparaît avant les passages de sa transcription ;
 *   2. une requête pour la page seulement : extrait avec ts_headline et transcription liée.
 *
 * Chaque identifiant porte sa source : « p:<id> » pour un paragraphe, « r:<id> » pour un résumé,
 * « t:<id> » pour une traduction (V2).
 *
 * La configuration de chaque langue est écrite dans le SQL et non liée en paramètre : c'est
 * elle qui permet à PostgreSQL de choisir l'index partiel. Elle vient de la liste fermée
 * ConfigurationRecherche. La contrainte de plan (recherche réservée au Pro et au Pack) relève
 * de l'appelant.
 */
class RechercheTranscriptions
{
    /** Marqueurs de surlignage : caractères de contrôle, jamais présents dans un texte transcrit. */
    private const DEBUT_SURLIGNAGE = "\x02";

    private const FIN_SURLIGNAGE = "\x03";

    /**
     * @param  ?Builder<Transcription>  $portee  Restriction facultative (vue filtrée de la bibliothèque) : seules
     *                                           les transcriptions de cette requête sont cherchées.
     * @return LengthAwarePaginator<int, ResultatRecherche>
     */
    public function rechercher(User $utilisateur, string $terme, int $parPage, int $page, ?Builder $portee = null): LengthAwarePaginator
    {
        $terme = $this->normaliser($terme);

        if ($terme === '') {
            return new Paginateur([], 0, $parPage, $page);
        }

        [$identifiants, $total] = $this->pageDIdentifiants($utilisateur, $terme, $parPage, $page, $portee);

        if ($total === 0) {
            return new Paginateur([], 0, $parPage, $page);
        }

        $trouves = $this->extraits($identifiants, $terme);
        $resultats = [];

        foreach ($identifiants as $identifiant) {
            if (isset($trouves[$identifiant])) {
                $resultats[] = $trouves[$identifiant];
            }
        }

        return new Paginateur($resultats, $total, $parPage, $page, ['pageName' => 'page']);
    }

    /**
     * Identifiants (« p:<id> » ou « r:<id> ») des passages de la page demandée, et nombre total de résultats.
     *
     * @return array{0: list<string>, 1: int}
     */
    private function pageDIdentifiants(User $utilisateur, string $terme, int $parPage, int $page, ?Builder $portee): array
    {
        [$trouves, $liaisons] = $this->cteTrouves($terme);
        [$restriction, $liaisonsRestriction] = $this->restriction($portee);

        $total = (int) DB::selectOne(
            "WITH trouves AS MATERIALIZED ({$trouves}) "
            .'SELECT count(*) AS total FROM trouves r JOIN transcriptions t ON t.id = r.transcription_id '
            ."WHERE t.user_id = ?{$restriction}",
            [...$liaisons, $utilisateur->getKey(), ...$liaisonsRestriction],
        )->total;

        if ($total === 0) {
            return [[], 0];
        }

        $lignes = DB::select(
            "WITH trouves AS MATERIALIZED ({$trouves}) "
            .'SELECT r.source, r.id FROM trouves r JOIN transcriptions t ON t.id = r.transcription_id '
            ."WHERE t.user_id = ?{$restriction} ORDER BY t.created_at DESC, t.id DESC, r.position LIMIT ? OFFSET ?",
            [...$liaisons, $utilisateur->getKey(), ...$liaisonsRestriction, $parPage, max(0, ($page - 1) * $parPage)],
        );

        return [array_map(fn ($ligne) => $ligne->source.':'.$ligne->id, $lignes), $total];
    }

    /**
     * Condition SQL qui limite la recherche aux transcriptions d'une requête (vue filtrée), avec ses valeurs liées.
     * La requête est transformée en sous-requête d'identifiants : elle reprend les mêmes index que la liste.
     *
     * @param  ?Builder<Transcription>  $portee
     * @return array{0: string, 1: list<mixed>}
     */
    private function restriction(?Builder $portee): array
    {
        if ($portee === null) {
            return ['', []];
        }

        $sousRequete = $portee->toBase()->reorder()->select('transcriptions.id');

        return [' AND t.id IN ('.$sousRequete->toSql().')', $sousRequete->getBindings()];
    }

    /**
     * Passages qui contiennent le terme : paragraphes et résumés terminés, un bloc par configuration
     * de langue, reliés par UNION ALL. Colonnes : source (« p » ou « r »), id, transcription_id, position.
     *
     * @return array{0: string, 1: list<string>} SQL du CTE et valeurs liées, dans l'ordre des « ? »
     */
    private function cteTrouves(string $terme): array
    {
        $blocs = [];
        $liaisons = [];
        $termine = StatutSortieIa::Termine->value;

        foreach (ConfigurationRecherche::toutes() as $configuration) {
            $blocs[] = "SELECT 'p' AS source, p.id, p.transcription_id, p.position FROM transcription_paragraphs p "
                ."WHERE p.config_recherche = '{$configuration}' "
                ."AND to_tsvector('{$configuration}', p.texte) @@ plainto_tsquery('{$configuration}', ?)";
            $liaisons[] = $terme;

            $blocs[] = "SELECT 'r' AS source, s.id, s.transcription_id, 0 AS position FROM ai_outputs s "
                ."WHERE s.statut = '{$termine}' AND s.fonctionnalite = 'resume' AND s.config_recherche = '{$configuration}' "
                ."AND to_tsvector('{$configuration}', s.texte_recherche) @@ plainto_tsquery('{$configuration}', ?)";
            $liaisons[] = $terme;

            // Traductions terminées (V2) : même index que les résumés, une source à part pour l'affichage.
            $blocs[] = "SELECT 't' AS source, s.id, s.transcription_id, 0 AS position FROM ai_outputs s "
                ."WHERE s.statut = '{$termine}' AND s.fonctionnalite = 'traduction' AND s.config_recherche = '{$configuration}' "
                ."AND to_tsvector('{$configuration}', s.texte_recherche) @@ plainto_tsquery('{$configuration}', ?)";
            $liaisons[] = $terme;
        }

        return [implode(' UNION ALL ', $blocs), $liaisons];
    }

    /**
     * Résultats des identifiants donnés, indexés par identifiant.
     *
     * @param  list<string>  $identifiants
     * @return array<string, ResultatRecherche>
     */
    private function extraits(array $identifiants, string $terme): array
    {
        $idsParagraphes = [];
        $idsResumes = [];
        $idsTraductions = [];

        foreach ($identifiants as $identifiant) {
            [$source, $id] = explode(':', $identifiant);

            if ($source === 'p') {
                $idsParagraphes[] = (int) $id;
            } elseif ($source === 't') {
                $idsTraductions[] = (int) $id;
            } else {
                $idsResumes[] = (int) $id;
            }
        }

        $resultats = [];

        foreach ($this->extraitsParagraphes($idsParagraphes, $terme) as $paragraphe) {
            $resultats['p:'.$paragraphe->getKey()] = new ResultatRecherche(
                transcription: $paragraphe->transcription,
                debutMs: $paragraphe->debut_ms,
                extraitHtml: $this->surligner((string) $paragraphe->extrait),
            );
        }

        foreach ($this->extraitsResumes($idsResumes, $terme) as $sortie) {
            $resultats['r:'.$sortie->getKey()] = new ResultatRecherche(
                transcription: $sortie->transcription,
                debutMs: null,
                extraitHtml: $this->surligner((string) $sortie->extrait),
            );
        }

        foreach ($this->extraitsResumes($idsTraductions, $terme) as $sortie) {
            $resultats['t:'.$sortie->getKey()] = new ResultatRecherche(
                transcription: $sortie->transcription,
                debutMs: null,
                extraitHtml: $this->surligner((string) $sortie->extrait),
                langueTraduction: (string) $sortie->format,
            );
        }

        return $resultats;
    }

    /** @return iterable<int, TranscriptionParagraph> */
    private function extraitsParagraphes(array $identifiants, string $terme): iterable
    {
        if ($identifiants === []) {
            return [];
        }

        return TranscriptionParagraph::query()
            ->with('transcription')
            ->whereIn('transcription_paragraphs.id', $identifiants)
            ->select('transcription_paragraphs.*')
            ->selectRaw(
                'ts_headline(transcription_paragraphs.config_recherche::regconfig, transcription_paragraphs.texte, '
                .'plainto_tsquery(transcription_paragraphs.config_recherche::regconfig, ?), ?) as extrait',
                [$terme, $this->optionsExtrait()],
            )
            ->get();
    }

    /** @return iterable<int, AiOutput> */
    private function extraitsResumes(array $identifiants, string $terme): iterable
    {
        if ($identifiants === []) {
            return [];
        }

        return AiOutput::query()
            ->with('transcription')
            ->whereIn('ai_outputs.id', $identifiants)
            ->select('ai_outputs.*')
            ->selectRaw(
                'ts_headline(ai_outputs.config_recherche::regconfig, ai_outputs.texte_recherche, '
                .'plainto_tsquery(ai_outputs.config_recherche::regconfig, ?), ?) as extrait',
                [$terme, $this->optionsExtrait()],
            )
            ->get();
    }

    private function optionsExtrait(): string
    {
        return sprintf(
            'StartSel=%s,StopSel=%s,MaxWords=%d,MinWords=%d,MaxFragments=1',
            self::DEBUT_SURLIGNAGE,
            self::FIN_SURLIGNAGE,
            (int) config('voxia.recherche.extrait_mots'),
            max(1, intdiv((int) config('voxia.recherche.extrait_mots'), 3)),
        );
    }

    /** Échappe le texte transcrit, puis remplace seulement les marqueurs par <mark>. */
    private function surligner(string $extraitBrut): string
    {
        return str_replace(
            [self::DEBUT_SURLIGNAGE, self::FIN_SURLIGNAGE],
            ['<mark class="rounded-sm bg-accent/25 px-0.5 text-texte">', '</mark>'],
            e($extraitBrut),
        );
    }

    private function normaliser(string $terme): string
    {
        $terme = trim(preg_replace('/\s+/u', ' ', $terme) ?? '');

        return Str::limit($terme, (int) config('voxia.recherche.longueur_max_terme'), '');
    }
}
