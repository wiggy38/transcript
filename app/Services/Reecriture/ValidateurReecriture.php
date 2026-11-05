<?php

namespace App\Services\Reecriture;

use App\Enums\FormatReecriture;
use App\Enums\LongueurReecriture;
use App\Exceptions\ErreurReecriture;

/**
 * Vérifie la sortie du modèle contre les règles de son format et la rend normalisée.
 *
 * - Limites (mots, caractères, nombre d'éléments) : voxia.ia.reecriture.*. Hors limite : ErreurReecriture,
 *   le service relance. Exception : le post LinkedIn trop long peut être raccourci en retirant des paragraphes
 *   entiers (« tronquer »), jamais coupé au milieu d'une phrase.
 * - Thread : un message trop long est découpé à la frontière d'un mot. Aucun mot n'est coupé.
 * - Nombres et liens : chaque nombre et chaque URL d'une sortie doivent figurer dans la transcription
 *   (contrôle minimal contre les ajouts ; il ne couvre ni les noms propres ni les affirmations).
 */
final class ValidateurReecriture
{
    /**
     * @param  array<string, mixed>  $structure  sortie décodée, déjà conforme au schéma
     * @return array<string, mixed> contenu normalisé, enregistré tel quel
     *
     * @throws ErreurReecriture
     */
    public function valider(FormatReecriture $format, array $structure, string $texteComplet, LongueurReecriture $longueur, bool $tronquer = false): array
    {
        $contenu = match ($format) {
            FormatReecriture::Article => $this->article($structure, $longueur),
            FormatReecriture::Newsletter => $this->newsletter($structure),
            FormatReecriture::LinkedIn => $this->linkedin($structure, $tronquer),
            FormatReecriture::Thread => $this->thread($structure),
        };

        $this->verifierSources($this->textes($contenu), $texteComplet);

        return $contenu;
    }

    /**
     * @param  array<string, mixed>  $s
     * @return array<string, mixed>
     */
    private function article(array $s, LongueurReecriture $longueur): array
    {
        $intertitres = [];

        foreach ((array) ($s['intertitres'] ?? []) as $bloc) {
            $intertitres[] = [
                'titre' => $this->texte($bloc['titre'] ?? null),
                'paragraphes' => $this->listeNonVide($bloc['paragraphes'] ?? []),
            ];
        }

        $contenu = [
            'titre' => $this->texte($s['titre'] ?? null),
            'introduction' => $this->texte($s['introduction'] ?? null),
            'intertitres' => $intertitres,
            'conclusion' => $this->texte($s['conclusion'] ?? null),
        ];

        $bornesIntertitres = config('voxia.ia.reecriture.article.intertitres');

        if (! $this->dans(count($intertitres), (int) $bornesIntertitres['min'], (int) $bornesIntertitres['max'])) {
            throw new ErreurReecriture('intertitres');
        }

        $bornesMots = config('voxia.ia.reecriture.article.mots.'.$longueur->value);
        $mots = $this->compterMots($this->textes($contenu));

        if (! $this->dans($mots, (int) $bornesMots['min'], (int) $bornesMots['max'])) {
            throw new ErreurReecriture('longueur');
        }

        return $contenu;
    }

    /**
     * @param  array<string, mixed>  $s
     * @return array<string, mixed>
     */
    private function newsletter(array $s): array
    {
        $contenu = [
            'objet' => $this->texte($s['objet'] ?? null),
            'apercu' => $this->texte($s['apercu'] ?? null),
            'accroche' => $this->texte($s['accroche'] ?? null),
            'corps' => $this->listeNonVide($s['corps'] ?? []),
            'appel_action' => $this->texte($s['appel_action'] ?? null),
        ];

        if (mb_strlen($contenu['objet']) > (int) config('voxia.ia.reecriture.newsletter.objet_caracteres')) {
            throw new ErreurReecriture('objet_trop_long');
        }

        if (mb_strlen($contenu['apercu']) > (int) config('voxia.ia.reecriture.newsletter.apercu_caracteres')) {
            throw new ErreurReecriture('apercu_trop_long');
        }

        $bornes = config('voxia.ia.reecriture.newsletter.corps');

        if (! $this->dans(count($contenu['corps']), (int) $bornes['min'], (int) $bornes['max'])) {
            throw new ErreurReecriture('corps');
        }

        return $contenu;
    }

    /**
     * @param  array<string, mixed>  $s
     * @return array<string, mixed>
     */
    private function linkedin(array $s, bool $tronquer): array
    {
        $accroche = $this->texte($s['accroche'] ?? null);
        $paragraphes = $this->listeNonVide($s['paragraphes'] ?? []);
        $max = (int) config('voxia.ia.reecriture.linkedin.caracteres_max');

        if (mb_strlen($accroche) > (int) config('voxia.ia.reecriture.linkedin.accroche_caracteres')) {
            throw new ErreurReecriture('accroche_trop_longue');
        }

        if ($tronquer) {
            // Dernier recours : on retire des paragraphes entiers depuis la fin, sans jamais en garder moins d'un.
            while (count($paragraphes) > 1 && $this->longueurLinkedin($accroche, $paragraphes) > $max) {
                array_pop($paragraphes);
            }
        }

        if ($this->longueurLinkedin($accroche, $paragraphes) > $max) {
            throw new ErreurReecriture('trop_long');
        }

        return ['accroche' => $accroche, 'paragraphes' => $paragraphes];
    }

    /**
     * @param  array<string, mixed>  $s
     * @return array<string, mixed>
     */
    private function thread(array $s): array
    {
        $budget = (int) config('voxia.ia.reecriture.thread.caracteres_message')
            - (int) config('voxia.ia.reecriture.thread.longueur_numero');

        $messages = [];

        foreach ((array) ($s['messages'] ?? []) as $bloc) {
            foreach ($this->decouper($this->texte(is_array($bloc) ? ($bloc['texte'] ?? null) : null), $budget) as $morceau) {
                $messages[] = $morceau;
            }
        }

        $bornes = config('voxia.ia.reecriture.thread.messages');

        if (! $this->dans(count($messages), (int) $bornes['min'], (int) $bornes['max'])) {
            throw new ErreurReecriture('nombre_messages');
        }

        return ['messages' => $messages];
    }

    /**
     * Découpe à la frontière d'un mot. Un mot plus long que le budget ne peut pas être découpé sans être
     * tronqué : la sortie est refusée.
     *
     * @return list<string>
     *
     * @throws ErreurReecriture
     */
    public function decouper(string $texte, int $budget): array
    {
        $morceaux = [];
        $reste = $texte;

        while (mb_strlen($reste) > $budget) {
            $tete = mb_substr($reste, 0, $budget + 1);
            $position = mb_strrpos($tete, ' ');

            if ($position === false || $position === 0) {
                throw new ErreurReecriture('mot_trop_long');
            }

            $morceaux[] = trim(mb_substr($reste, 0, $position));
            $reste = trim(mb_substr($reste, $position));
        }

        $morceaux[] = $reste;

        return $morceaux;
    }

    /**
     * @param  list<string>  $textes
     *
     * @throws ErreurReecriture
     */
    private function verifierSources(array $textes, string $texteComplet): void
    {
        foreach ($textes as $texte) {
            preg_match_all('/\d+/u', $texte, $nombres);

            foreach ($nombres[0] ?? [] as $nombre) {
                if (preg_match('/(?<!\d)'.preg_quote($nombre, '/').'(?!\d)/u', $texteComplet) !== 1) {
                    throw new ErreurReecriture('chiffre_absent');
                }
            }

            preg_match_all('#(?:https?://|www\.)\S+#iu', $texte, $liens);

            foreach ($liens[0] ?? [] as $lien) {
                $lien = rtrim($lien, '.,;:!?)»"\'');

                if (mb_stripos($texteComplet, $lien) === false) {
                    throw new ErreurReecriture('lien_absent');
                }
            }
        }
    }

    /** Texte non vide, espaces réduits. Une valeur vide ou absente refuse la sortie. */
    private function texte(mixed $valeur): string
    {
        if (! is_string($valeur)) {
            throw new ErreurReecriture('vide');
        }

        $texte = trim((string) preg_replace('/\s+/u', ' ', $valeur));

        if ($texte === '') {
            throw new ErreurReecriture('vide');
        }

        return $texte;
    }

    /**
     * @return list<string>
     *
     * @throws ErreurReecriture
     */
    private function listeNonVide(mixed $valeur): array
    {
        $liste = [];

        foreach ((array) $valeur as $element) {
            $liste[] = $this->texte($element);
        }

        if ($liste === []) {
            throw new ErreurReecriture('vide');
        }

        return $liste;
    }

    /**
     * Toutes les chaînes d'un contenu normalisé, pour les contrôles de nombres, de liens et de mots.
     *
     * @param  array<mixed>  $contenu
     * @return list<string>
     */
    private function textes(array $contenu): array
    {
        $textes = [];

        array_walk_recursive($contenu, function ($valeur) use (&$textes) {
            if (is_string($valeur)) {
                $textes[] = $valeur;
            }
        });

        return $textes;
    }

    /** @param  list<string>  $textes */
    private function compterMots(array $textes): int
    {
        $mots = 0;

        foreach ($textes as $texte) {
            $mots += count(array_filter(preg_split('/\s+/u', $texte) ?: [], fn (string $m) => $m !== ''));
        }

        return $mots;
    }

    /**
     * Longueur du post tel qu'il est copié : accroche et paragraphes séparés par une ligne vide.
     *
     * @param  list<string>  $paragraphes
     */
    private function longueurLinkedin(string $accroche, array $paragraphes): int
    {
        return mb_strlen(implode("\n\n", [$accroche, ...$paragraphes]));
    }

    private function dans(int $valeur, int $min, int $max): bool
    {
        return $valeur >= $min && $valeur <= $max;
    }
}
