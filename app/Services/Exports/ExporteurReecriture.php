<?php

namespace App\Services\Exports;

use App\Enums\FormatExportReecriture;
use App\Enums\FormatReecriture;
use App\Models\AiOutput;
use InvalidArgumentException;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

/**
 * Export d'une réécriture : TXT (Pro et Business), Markdown et DOCX (Business). Chaque format passe par la
 * même représentation intermédiaire (titre, section, paragraphe, liste numérotée) : les trois exports
 * restent alignés sur le même contenu.
 */
final class ExporteurReecriture
{
    public function exporter(AiOutput $sortie, FormatExportReecriture $export): string
    {
        $format = FormatReecriture::from($sortie->format);
        $blocs = $this->blocs($format, $sortie->contenu ?? []);

        return match ($export) {
            FormatExportReecriture::Txt => $this->txt($blocs),
            FormatExportReecriture::Markdown => $this->markdown($blocs),
            FormatExportReecriture::Docx => $this->docx($blocs),
            default => throw new InvalidArgumentException("Export de réécriture non géré : {$export->value}"),
        };
    }

    /**
     * Titre du document : le titre de l'article, sinon le nom du format.
     *
     * @param  array<string, mixed>  $contenu
     */
    public function titre(FormatReecriture $format, array $contenu): string
    {
        $titre = $format === FormatReecriture::Article ? (string) ($contenu['titre'] ?? '') : '';

        return $titre !== '' ? $titre : __('reecriture.formats.'.$format->value);
    }

    /**
     * Représentation intermédiaire : chaque bloc a un type (titre, section, paragraphe, liste) et un contenu.
     *
     * @param  array<string, mixed>  $contenu
     * @return list<array{type: string, texte?: string, items?: list<string>}>
     */
    public function blocs(FormatReecriture $format, array $contenu): array
    {
        $libelles = __('reecriture.export');

        return match ($format) {
            FormatReecriture::Article => [
                ['type' => 'titre', 'texte' => (string) $contenu['titre']],
                ['type' => 'paragraphe', 'texte' => (string) $contenu['introduction']],
                ...array_merge(...array_map(fn (array $bloc) => [
                    ['type' => 'section', 'texte' => (string) $bloc['titre']],
                    ...array_map(fn (string $p) => ['type' => 'paragraphe', 'texte' => $p], $bloc['paragraphes']),
                ], $contenu['intertitres'])),
                ['type' => 'section', 'texte' => $libelles['conclusion']],
                ['type' => 'paragraphe', 'texte' => (string) $contenu['conclusion']],
            ],
            FormatReecriture::Newsletter => [
                ['type' => 'paragraphe', 'texte' => $libelles['objet'].' : '.$contenu['objet']],
                ['type' => 'paragraphe', 'texte' => $libelles['apercu'].' : '.$contenu['apercu']],
                ['type' => 'paragraphe', 'texte' => (string) $contenu['accroche']],
                ...array_map(fn (string $p) => ['type' => 'paragraphe', 'texte' => $p], $contenu['corps']),
                ['type' => 'paragraphe', 'texte' => (string) $contenu['appel_action']],
            ],
            FormatReecriture::LinkedIn => [
                ['type' => 'paragraphe', 'texte' => (string) $contenu['accroche']],
                ...array_map(fn (string $p) => ['type' => 'paragraphe', 'texte' => $p], $contenu['paragraphes']),
            ],
            FormatReecriture::Thread => [
                ['type' => 'liste', 'items' => $this->numerotes($contenu['messages'])],
            ],
        };
    }

    /**
     * Numéro « k/N » en tête de chaque message du thread. Le numéro est ajouté ici, jamais par le modèle.
     *
     * @param  list<string>  $messages
     * @return list<string>
     */
    public function numerotes(array $messages): array
    {
        $total = count($messages);

        return array_map(fn (string $m, int $i) => ($i + 1).'/'.$total.' '.$m, $messages, array_keys($messages));
    }

    /** @param  list<array{type: string, texte?: string, items?: list<string>}>  $blocs */
    private function txt(array $blocs): string
    {
        $lignes = [];

        foreach ($blocs as $bloc) {
            if ($bloc['type'] === 'liste') {
                array_push($lignes, ...$bloc['items']);
                $lignes[] = '';

                continue;
            }

            $lignes[] = $bloc['texte'];
            $lignes[] = '';
        }

        return rtrim(implode("\n", $lignes))."\n";
    }

    /** @param  list<array{type: string, texte?: string, items?: list<string>}>  $blocs */
    private function markdown(array $blocs): string
    {
        $morceaux = [];

        foreach ($blocs as $bloc) {
            $morceaux[] = match ($bloc['type']) {
                'titre' => '# '.$bloc['texte'],
                'section' => '## '.$bloc['texte'],
                'liste' => implode("\n\n", array_map(fn (string $item) => '- '.$item, $bloc['items'])),
                default => $bloc['texte'],
            };
        }

        return implode("\n\n", $morceaux)."\n";
    }

    /** @param  list<array{type: string, texte?: string, items?: list<string>}>  $blocs */
    private function docx(array $blocs): string
    {
        $document = new PhpWord;
        $section = $document->addSection();

        foreach ($blocs as $bloc) {
            if ($bloc['type'] === 'liste') {
                foreach ($bloc['items'] as $item) {
                    $section->addText($item);
                }

                continue;
            }

            match ($bloc['type']) {
                'titre' => $section->addTitle($bloc['texte'], 1),
                'section' => $section->addTitle($bloc['texte'], 2),
                default => $section->addText($bloc['texte']),
            };
        }

        // PhpWord n'écrit que dans un fichier : on passe par un fichier temporaire, supprimé aussitôt.
        $chemin = tempnam(sys_get_temp_dir(), 'reecriture');
        IOFactory::createWriter($document, 'Word2007')->save($chemin);
        $contenu = (string) file_get_contents($chemin);
        unlink($chemin);

        return $contenu;
    }
}
