<?php

namespace App\Services\Recherche;

use App\Data\ResultatRecherche;
use App\Models\TranscriptionParagraph;
use App\Models\User;
use App\Support\ConfigurationRecherche;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator as Paginateur;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Recherche plein texte dans toutes les transcriptions d'un compte (cahier F5).
 *
 * Deux temps, pour ne calculer l'extrait que des résultats affichés :
 *   1. les paragraphes trouvés, par langue (un index GIN partiel par configuration), dans un
 *      CTE MATERIALIZED : la recherche dans l'index se fait en premier, quelles que soient les
 *      statistiques de la table. Sans ce CTE, PostgreSQL peut choisir, juste après un import
 *      massif (statistiques pas encore recalculées), une jointure par transcription qui prend
 *      plus d'une seconde. Le CTE joint ensuite les transcriptions du compte, triées par date ;
 *   2. une requête pour la page seulement : extrait avec ts_headline et transcription liée.
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
     * @return LengthAwarePaginator<int, ResultatRecherche>
     */
    public function rechercher(User $utilisateur, string $terme, int $parPage, int $page): LengthAwarePaginator
    {
        $terme = $this->normaliser($terme);

        if ($terme === '') {
            return new Paginateur([], 0, $parPage, $page);
        }

        [$identifiants, $total] = $this->pageDIdentifiants($utilisateur, $terme, $parPage, $page);

        if ($total === 0) {
            return new Paginateur([], 0, $parPage, $page);
        }

        $ordre = array_flip($identifiants);

        $resultats = $this->extraits($identifiants, $terme)
            ->sortBy(fn (TranscriptionParagraph $paragraphe) => $ordre[$paragraphe->getKey()])
            ->values()
            ->map(fn (TranscriptionParagraph $paragraphe) => new ResultatRecherche(
                transcription: $paragraphe->transcription,
                debutMs: $paragraphe->debut_ms,
                extraitHtml: $this->surligner((string) $paragraphe->extrait),
            ));

        return new Paginateur($resultats->all(), $total, $parPage, $page, ['pageName' => 'page']);
    }

    /**
     * Identifiants des paragraphes de la page demandée, et nombre total de résultats.
     *
     * @return array{0: list<int>, 1: int}
     */
    private function pageDIdentifiants(User $utilisateur, string $terme, int $parPage, int $page): array
    {
        [$trouves, $liaisons] = $this->cteTrouves($terme);

        $total = (int) DB::selectOne(
            "WITH trouves AS MATERIALIZED ({$trouves}) "
            .'SELECT count(*) AS total FROM trouves r JOIN transcriptions t ON t.id = r.transcription_id WHERE t.user_id = ?',
            [...$liaisons, $utilisateur->getKey()],
        )->total;

        if ($total === 0) {
            return [[], 0];
        }

        $lignes = DB::select(
            "WITH trouves AS MATERIALIZED ({$trouves}) "
            .'SELECT r.id FROM trouves r JOIN transcriptions t ON t.id = r.transcription_id '
            .'WHERE t.user_id = ? ORDER BY t.created_at DESC, t.id DESC, r.position LIMIT ? OFFSET ?',
            [...$liaisons, $utilisateur->getKey(), $parPage, max(0, ($page - 1) * $parPage)],
        );

        return [array_map(fn ($ligne) => (int) $ligne->id, $lignes), $total];
    }

    /**
     * Paragraphes qui contiennent le terme, un bloc par configuration de langue, reliés par UNION ALL.
     *
     * @return array{0: string, 1: list<string>} SQL du CTE et valeurs liées, dans l'ordre des « ? »
     */
    private function cteTrouves(string $terme): array
    {
        $blocs = [];
        $liaisons = [];

        foreach (ConfigurationRecherche::toutes() as $configuration) {
            $blocs[] = 'SELECT p.id, p.transcription_id, p.position FROM transcription_paragraphs p '
                ."WHERE p.config_recherche = '{$configuration}' "
                ."AND to_tsvector('{$configuration}', p.texte) @@ plainto_tsquery('{$configuration}', ?)";
            $liaisons[] = $terme;
        }

        return [implode(' UNION ALL ', $blocs), $liaisons];
    }

    /** @return Collection<int, TranscriptionParagraph> */
    private function extraits(array $identifiants, string $terme)
    {
        $options = sprintf(
            'StartSel=%s,StopSel=%s,MaxWords=%d,MinWords=%d,MaxFragments=1',
            self::DEBUT_SURLIGNAGE,
            self::FIN_SURLIGNAGE,
            (int) config('voxia.recherche.extrait_mots'),
            max(1, intdiv((int) config('voxia.recherche.extrait_mots'), 3)),
        );

        return TranscriptionParagraph::query()
            ->with('transcription')
            ->whereIn('transcription_paragraphs.id', $identifiants)
            ->select('transcription_paragraphs.*')
            ->selectRaw(
                'ts_headline(transcription_paragraphs.config_recherche::regconfig, transcription_paragraphs.texte, '
                .'plainto_tsquery(transcription_paragraphs.config_recherche::regconfig, ?), ?) as extrait',
                [$terme, $options],
            )
            ->get();
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
