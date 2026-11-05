<?php

namespace App\Services\Reecriture;

use App\Data\Ia\RequeteModele;
use App\Enums\FormatReecriture;
use App\Enums\LongueurReecriture;
use App\Enums\TonReecriture;

/**
 * Prompts de la réécriture. Le prompt se compose de resources/ia/reecriture/base.txt, puis du gabarit du
 * format ; les valeurs (limites, ton, langue) viennent de config/voxia.php. La transcription part dans un
 * bloc <donnee>, échappé par AnthropicLanguageModel : elle ne peut pas changer la consigne.
 */
final class ConstructeurRequeteReecriture
{
    /** Noms des langues de sortie, en français (le prompt est écrit en français quelle que soit l'interface). */
    private const NOMS_LANGUES = [
        'fr' => 'français',
        'en' => 'anglais',
        'es' => 'espagnol',
        'de' => 'allemand',
        'it' => 'italien',
        'pt' => 'portugais',
    ];

    public function pour(
        FormatReecriture $format,
        TonReecriture $ton,
        LongueurReecriture $longueur,
        string $langue,
        string $texteComplet,
        ?int $utilisateurId,
        ?int $sortieId,
        bool $relance = false,
    ): RequeteModele {
        $instructions = $this->instructions($format, $ton, $longueur, $langue);

        if ($relance) {
            $instructions .= "\n\nLa réponse précédente ne respectait pas les règles du format : renvoie une nouvelle version qui les respecte toutes.";
        }

        return new RequeteModele(
            fonctionnalite: 'reecriture',
            instructions: $instructions,
            donnees: [$texteComplet],
            maxTokens: (int) config("voxia.ia.reecriture.{$format->value}.max_tokens_sortie"),
            userId: $utilisateurId,
            aiOutputId: $sortieId,
        );
    }

    /** Le prompt complet, sans la transcription. */
    public function instructions(FormatReecriture $format, TonReecriture $ton, LongueurReecriture $longueur, string $langue): string
    {
        $dossier = resource_path('ia/reecriture');
        $gabarit = trim((string) file_get_contents($dossier.'/base.txt'))
            ."\n\n".trim((string) file_get_contents($dossier.'/'.$format->value.'.txt'));

        $tons = require $dossier.'/tons.php';
        $bornesMots = config('voxia.ia.reecriture.article.mots.'.$longueur->value);

        $valeurs = [
            '{format}' => $format->nomPrompt(),
            '{langue}' => self::NOMS_LANGUES[$langue] ?? $langue,
            '{ton}' => $tons[$ton->value] ?? $tons['professionnel'],
            '{longueur}' => $format === FormatReecriture::Article
                ? "Longueur : entre {$bornesMots['min']} et {$bornesMots['max']} mots."
                : '',
            '{intertitres_min}' => config('voxia.ia.reecriture.article.intertitres.min'),
            '{intertitres_max}' => config('voxia.ia.reecriture.article.intertitres.max'),
            '{mots_min}' => $bornesMots['min'],
            '{mots_max}' => $bornesMots['max'],
            '{objet_caracteres}' => config('voxia.ia.reecriture.newsletter.objet_caracteres'),
            '{apercu_caracteres}' => config('voxia.ia.reecriture.newsletter.apercu_caracteres'),
            '{corps_min}' => config('voxia.ia.reecriture.newsletter.corps.min'),
            '{corps_max}' => config('voxia.ia.reecriture.newsletter.corps.max'),
            '{accroche_caracteres}' => config('voxia.ia.reecriture.linkedin.accroche_caracteres'),
            '{linkedin_caracteres}' => config('voxia.ia.reecriture.linkedin.caracteres_max'),
            '{messages_min}' => config('voxia.ia.reecriture.thread.messages.min'),
            '{messages_max}' => config('voxia.ia.reecriture.thread.messages.max'),
            '{thread_caracteres_cible}' => config('voxia.ia.reecriture.thread.caracteres_cible'),
            '{thread_caracteres}' => config('voxia.ia.reecriture.thread.caracteres_message'),
        ];

        return strtr($gabarit, $valeurs);
    }
}
