<?php

namespace App\Services\Ia;

use App\Contracts\LanguageModel;
use App\Data\Ia\ReponseModele;
use App\Data\Ia\RequeteModele;
use App\Enums\MotifErreurModele;
use App\Exceptions\ErreurModele;
use App\Models\AppelIa;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fournisseur du modèle de langage : API Messages d'Anthropic (le fournisseur déjà utilisé par F7).
 *
 * - Clé, URL, version d'API : services.anthropic*. Modèle, tarifs, délais, tentatives : voxia.ia.modele.
 * - Erreurs transitoires (délai, débit, connexion, 5xx) : nouvel appel, jusqu'à « tentatives » appels au total.
 * - Refus, troncature, sortie vide : pas de nouvel appel.
 * - Sortie structurée invalide : relancée « relances_sortie_invalide » fois, puis échec.
 * - Chaque tentative est écrite dans appels_ia (statut, jetons, durée, coût estimé).
 * - Les données sont échappées (&lt; &gt; &amp;) : une donnée ne peut pas refermer sa balise ni ajouter une instruction.
 */
final class AnthropicLanguageModel implements LanguageModel
{
    public function __construct(private readonly ValidateurSchema $validateur) {}

    public function generer(RequeteModele $requete): ReponseModele
    {
        return $this->executer($requete, null);
    }

    public function genererStructure(RequeteModele $requete, array $schema): ReponseModele
    {
        return $this->executer($requete, $schema);
    }

    /** @param  array<string, mixed>|null  $schema */
    private function executer(RequeteModele $requete, ?array $schema): ReponseModele
    {
        $cle = (string) config('services.anthropic.key');

        if ($cle === '') {
            Log::error('anthropic: clé d’API absente de la configuration');

            throw new ErreurModele(MotifErreurModele::CleAbsente);
        }

        $modele = (string) config('voxia.ia.modele.modele');

        if (! isset(config('voxia.ia.modele.tarifs')[$modele])) {
            throw new ErreurModele(MotifErreurModele::ModeleInconnu);
        }

        $tentativesMax = max(1, (int) config('voxia.ia.modele.tentatives'));
        $relancesSortie = $schema === null ? 0 : max(0, (int) config('voxia.ia.modele.relances_sortie_invalide'));
        $transitoires = 0;
        $sortiesInvalides = 0;
        $tentative = 0;
        $coutTotal = 0.0;

        while (true) {
            $tentative++;
            $debut = hrtime(true);

            try {
                $reponse = Http::withHeaders([
                    'x-api-key' => $cle,
                    'anthropic-version' => (string) config('services.anthropic_messages.version'),
                ])
                    ->acceptJson()
                    ->asJson()
                    ->connectTimeout((int) config('voxia.ia.modele.connexion_secondes'))
                    ->timeout((int) config('voxia.ia.modele.delai_secondes'))
                    ->post($this->adresse(), $this->corps($requete, $modele, $schema));
            } catch (ConnectionException $exception) {
                $motif = $this->estDelai($exception) ? MotifErreurModele::Delai : MotifErreurModele::Connexion;
                $this->journaliser($requete, $tentative, $modele, 0, 0, 0.0, $debut, $motif);

                if (++$transitoires < $tentativesMax) {
                    $this->attendre();

                    continue;
                }

                throw new ErreurModele($motif);
            }

            $json = $reponse->json();
            $json = is_array($json) ? $json : [];
            $usage = is_array($json['usage'] ?? null) ? $json['usage'] : [];
            $tokensEntree = (int) ($usage['input_tokens'] ?? 0);
            $tokensSortie = (int) ($usage['output_tokens'] ?? 0);
            $cout = $this->cout($modele, $tokensEntree, $tokensSortie);
            $coutTotal += $cout;

            if (! $reponse->successful()) {
                $motif = $this->motifHttp($reponse);
                $this->journaliser($requete, $tentative, $modele, $tokensEntree, $tokensSortie, $cout, $debut, $motif);

                if (in_array($motif, [MotifErreurModele::Debit, MotifErreurModele::Http], true)
                    && $this->estTransitoire($reponse) && ++$transitoires < $tentativesMax) {
                    $this->attendre();

                    continue;
                }

                $this->journaliserEchecHttp($reponse);

                throw new ErreurModele($motif, $coutTotal);
            }

            $arret = $json['stop_reason'] ?? null;

            if ($arret === 'refusal') {
                $this->journaliser($requete, $tentative, $modele, $tokensEntree, $tokensSortie, $cout, $debut, MotifErreurModele::Refus);

                throw new ErreurModele(MotifErreurModele::Refus, $coutTotal);
            }

            if ($arret === 'max_tokens') {
                $this->journaliser($requete, $tentative, $modele, $tokensEntree, $tokensSortie, $cout, $debut, MotifErreurModele::Tronquee);

                throw new ErreurModele(MotifErreurModele::Tronquee, $coutTotal);
            }

            $texte = $this->texteDeLaReponse($json);

            if ($texte === null || $texte === '') {
                $this->journaliser($requete, $tentative, $modele, $tokensEntree, $tokensSortie, $cout, $debut, MotifErreurModele::AucunContenu);

                throw new ErreurModele(MotifErreurModele::AucunContenu, $coutTotal);
            }

            if ($schema === null) {
                $this->journaliser($requete, $tentative, $modele, $tokensEntree, $tokensSortie, $cout, $debut, null);

                return new ReponseModele(
                    texte: $texte,
                    structure: null,
                    modele: (string) ($json['model'] ?? $modele),
                    tokensEntree: $tokensEntree,
                    tokensSortie: $tokensSortie,
                    coutUsd: $cout,
                    dureeMs: $this->dureeMs($debut),
                );
            }

            $donnees = json_decode($texte, true);

            if (is_array($donnees) && $this->validateur->erreur($donnees, $schema) === null) {
                $this->journaliser($requete, $tentative, $modele, $tokensEntree, $tokensSortie, $cout, $debut, null);

                return new ReponseModele(
                    texte: null,
                    structure: $donnees,
                    modele: (string) ($json['model'] ?? $modele),
                    tokensEntree: $tokensEntree,
                    tokensSortie: $tokensSortie,
                    coutUsd: $cout,
                    dureeMs: $this->dureeMs($debut),
                );
            }

            $this->journaliser($requete, $tentative, $modele, $tokensEntree, $tokensSortie, $cout, $debut, MotifErreurModele::ReponseInvalide);

            if (++$sortiesInvalides > $relancesSortie) {
                throw new ErreurModele(MotifErreurModele::ReponseInvalide, $coutTotal);
            }
        }
    }

    /**
     * Instructions dans « system », données dans des blocs séparés du message utilisateur.
     *
     * @param  array<string, mixed>|null  $schema
     * @return array<string, mixed>
     */
    private function corps(RequeteModele $requete, string $modele, ?array $schema): array
    {
        $blocs = [];

        foreach ($requete->donnees as $index => $donnee) {
            $echappee = htmlspecialchars($donnee, ENT_NOQUOTES, 'UTF-8');
            $blocs[] = ['type' => 'text', 'text' => "<donnee index=\"{$index}\">\n{$echappee}\n</donnee>"];
        }

        $corps = [
            'model' => $modele,
            'max_tokens' => $requete->maxTokens,
            'system' => $requete->instructions,
            'messages' => [[
                'role' => 'user',
                'content' => $blocs !== [] ? $blocs : [['type' => 'text', 'text' => 'Traite la demande.']],
            ]],
        ];

        if ($schema !== null) {
            $corps['output_config'] = ['format' => ['type' => 'json_schema', 'schema' => $schema]];
        }

        return $corps;
    }

    /** @param  array<string, mixed>  $json */
    private function texteDeLaReponse(array $json): ?string
    {
        foreach ((array) ($json['content'] ?? []) as $bloc) {
            if (is_array($bloc) && ($bloc['type'] ?? null) === 'text' && is_string($bloc['text'] ?? null)) {
                return $bloc['text'];
            }
        }

        return null;
    }

    private function motifHttp(Response $reponse): MotifErreurModele
    {
        return $reponse->status() === 429 ? MotifErreurModele::Debit : MotifErreurModele::Http;
    }

    private function estTransitoire(Response $reponse): bool
    {
        return $reponse->status() === 429 || $reponse->status() >= 500;
    }

    private function estDelai(ConnectionException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'timed out') || str_contains($message, 'timeout');
    }

    private function cout(string $modele, int $tokensEntree, int $tokensSortie): float
    {
        $tarif = config("voxia.ia.modele.tarifs.{$modele}");

        $entree = $tokensEntree * (float) $tarif['entree'] / 1_000_000;
        $sortie = $tokensSortie * (float) $tarif['sortie'] / 1_000_000;

        return round($entree + $sortie, 6);
    }

    private function journaliser(
        RequeteModele $requete,
        int $tentative,
        string $modele,
        int $tokensEntree,
        int $tokensSortie,
        float $cout,
        int|float $debut,
        ?MotifErreurModele $motif,
    ): void {
        AppelIa::create([
            'user_id' => $requete->userId,
            'ai_output_id' => $requete->aiOutputId,
            'fonctionnalite' => $requete->fonctionnalite,
            'fournisseur' => 'anthropic',
            'modele' => $modele,
            'tentative' => $tentative,
            'tokens_entree' => $tokensEntree,
            'tokens_sortie' => $tokensSortie,
            'duree_ms' => $this->dureeMs($debut),
            'cout_estime_usd' => $cout,
            'statut' => $motif?->value ?? 'ok',
        ]);
    }

    private function journaliserEchecHttp(Response $reponse): void
    {
        Log::warning('anthropic: échec d’appel', [
            'statut' => $reponse->status(),
            'corps' => mb_substr($reponse->body(), 0, (int) config('voxia.transcription.error_body_log_chars')),
        ]);
    }

    private function dureeMs(int|float $debut): int
    {
        return (int) round((hrtime(true) - $debut) / 1_000_000);
    }

    private function attendre(): void
    {
        $ms = (int) config('voxia.ia.modele.delai_entre_tentatives_ms');

        if ($ms > 0) {
            usleep($ms * 1000);
        }
    }

    private function adresse(): string
    {
        return rtrim((string) config('services.anthropic_messages.base_url'), '/').'/v1/messages';
    }
}
