<?php

namespace App\Services\Analyse;

use App\Contracts\ScriptAnalyzer;
use App\Data\EntreeAnalyse;
use App\Data\MotTranscrit;
use App\Data\Paragraphe;
use App\Data\PartieAnalysee;
use App\Data\ResultatFournisseur;
use App\Enums\EtapeTraitement;
use App\Enums\StatutAnalyseScript;
use App\Exceptions\ErreurAnalyse;
use App\Models\ProcessingLog;
use App\Models\ScriptAnalysis;
use App\Models\ScriptPart;
use App\Models\Transcription;
use App\Services\Quota\QuotaService;
use Illuminate\Support\Facades\DB;

/**
 * Exécute une analyse de script (F7) : appel au fournisseur, validation, enregistrement des cinq
 * parties, décompte d'un crédit IA. Une réponse invalide ou un échec d'appel est relancé
 * voxia.script_analysis.relances fois ; l'échec final ne décompte rien.
 *
 * Le coût réel de chaque appel (y compris un appel rejeté, facturé) va dans processing_logs.
 */
final class ServiceAnalyseScript
{
    public function __construct(
        private readonly ScriptAnalyzer $analyseur,
        private readonly ValidateurAnalyse $validateur,
        private readonly QuotaService $quotas,
    ) {}

    public function executer(ScriptAnalysis $analyse): void
    {
        $transcription = $analyse->transcription;
        $debut = hrtime(true);

        $analyse->update([
            'statut' => StatutAnalyseScript::Analyse,
            'date_debut' => now(),
            'date_echec' => null,
        ]);

        try {
            [$resultat, $parties, $cout] = $this->analyserAvecRelances($this->entree($transcription));
        } catch (ErreurAnalyse $erreur) {
            $analyse->update([
                'statut' => StatutAnalyseScript::Echec,
                'date_echec' => now(),
                'cout_estime_usd' => $erreur->coutUsd,
            ]);

            $this->journaliser($transcription, $debut, false, $erreur->getMessage(), $erreur->coutUsd);

            return;
        }

        DB::transaction(function () use ($analyse, $transcription, $resultat, $parties, $cout) {
            ScriptPart::where('script_analysis_id', $analyse->getKey())->delete();

            foreach ($parties as $index => $partie) {
                ScriptPart::create([
                    'script_analysis_id' => $analyse->getKey(),
                    'position' => $index + 1,
                    'type' => $partie->type,
                    'debut_ms' => $partie->debutMs,
                    'fin_ms' => $partie->finMs,
                    'texte' => $partie->texte,
                    'detectee' => $partie->detectee,
                ]);
            }

            $analyse->update([
                'statut' => StatutAnalyseScript::Termine,
                'date_termine' => now(),
                'fournisseur' => $resultat->fournisseur,
                'cout_estime_usd' => $cout,
            ]);

            $this->quotas->consommerCreditIa($transcription->user, $analyse);
        });

        $this->journaliser($transcription, $debut, true, null, $cout);
    }

    /**
     * Une tentative, puis voxia.script_analysis.relances relances. Renvoie le résultat, les parties
     * validées et le coût cumulé de tous les appels.
     *
     * @return array{0: ResultatFournisseur, 1: list<PartieAnalysee>, 2: float}
     *
     * @throws ErreurAnalyse avec le coût cumulé
     */
    private function analyserAvecRelances(EntreeAnalyse $entree): array
    {
        $relances = max(0, (int) config('voxia.script_analysis.relances'));
        $cout = 0.0;
        $derniere = 'analyse impossible';

        for ($essai = 0; $essai <= $relances; $essai++) {
            try {
                $resultat = $this->analyseur->analyser($entree);
                $cout += $resultat->coutUsd;

                return [$resultat, $this->validateur->valider($resultat->parties, $entree->mots), $cout];
            } catch (ErreurAnalyse $erreur) {
                $cout += $erreur->coutUsd;
                $derniere = $erreur->getMessage();
            }
        }

        throw new ErreurAnalyse($derniere, $cout);
    }

    private function entree(Transcription $transcription): EntreeAnalyse
    {
        $mots = $transcription->words()->get()
            ->map(fn ($mot) => new MotTranscrit((string) $mot->texte, (int) $mot->debut_ms, (int) $mot->fin_ms))
            ->values()->all();

        $paragraphes = $transcription->paragraphs()->get()
            ->map(fn ($paragraphe) => new Paragraphe((int) $paragraphe->debut_ms, (int) $paragraphe->fin_ms, (string) $paragraphe->texte))
            ->values()->all();

        return new EntreeAnalyse(
            paragraphes: $paragraphes,
            mots: $mots,
            dureeMs: (int) $transcription->duree_secondes * 1000,
        );
    }

    private function journaliser(Transcription $transcription, int $debut, bool $succes, ?string $message, float $cout): void
    {
        ProcessingLog::create([
            'transcription_id' => $transcription->getKey(),
            'etape' => EtapeTraitement::AnalyseScript,
            'duree_ms' => intdiv(hrtime(true) - $debut, 1_000_000),
            'succes' => $succes,
            'message' => $message !== null ? mb_substr($message, 0, 500) : null,
            'cout_usd' => $cout,
        ]);
    }
}
