<?php

namespace App\Services\Traduction;

use App\Enums\StatutSortieIa;
use App\Enums\StatutTranscription;
use App\Exceptions\TraductionRefusee;
use App\Jobs\GenererTraduction;
use App\Models\AiOutput;
use App\Models\Transcription;
use App\Models\User;
use App\Services\Plans\PlanFeatures;
use App\Services\Quota\QuotaService;
use Illuminate\Support\Facades\DB;

/**
 * Lance la traduction d'une transcription dans une ou plusieurs langues. Toutes les règles sont vérifiées avant
 * toute écriture : si le solde ne couvre pas l'ensemble des langues facturées, rien n'est lancé.
 *
 * - Accès par crédits, pour tous les plans : un plan sans crédits IA (Gratuit, Pack) reçoit « crédit insuffisant ».
 * - 1 crédit par heure entamée et par langue (voxia.ia.traduction.credits_par_heure), affiché avant lancement.
 * - Une langue déjà terminée se rouvre sans coût ; une langue en cours n'est ni relancée ni facturée deux fois.
 * - Chaque langue facturée est une sortie (ai_outputs) : le crédit n'est décompté qu'à la réussite.
 */
final class LancerTraduction
{
    public function __construct(
        private readonly QuotaService $quotas,
        private readonly PlanFeatures $droits,
        private readonly ConstructeurSegments $segments,
    ) {}

    /** Langue de la transcription : celle forcée à la transcription, sinon celle détectée. */
    public function langueSource(Transcription $transcription): string
    {
        return (string) ($transcription->langue_forcee ?? $transcription->langue_detectee ?? '');
    }

    /** Langues cibles proposées : les langues de traduction, sans la langue source. */
    public function languesCibles(Transcription $transcription): array
    {
        $source = $this->langueSource($transcription);

        return array_values(array_filter(
            (array) config('voxia.ia.traduction.langues'),
            fn (string $langue) => $langue !== $source,
        ));
    }

    /** Heures facturées pour une langue : 1 h 01 = 2 heures entamées. */
    public function heures(Transcription $transcription): int
    {
        return (int) ceil(max(0, (int) $transcription->duree_secondes) / 3600);
    }

    public function coutParLangue(Transcription $transcription): int
    {
        return $this->heures($transcription) * (int) config('voxia.ia.traduction.credits_par_heure');
    }

    /**
     * Langues qui seraient facturées si on les lançait maintenant.
     *
     * @param  list<string>  $langues
     * @return list<string>
     */
    public function aFacturer(Transcription $transcription, array $langues): array
    {
        $facturer = [];

        foreach ($this->uniques($langues) as $langue) {
            $existante = $this->existante($transcription, $langue);

            if ($existante?->statut?->enCours() || $existante?->statut === StatutSortieIa::Termine) {
                continue;
            }

            $facturer[] = $langue;
        }

        return $facturer;
    }

    /** @param  list<string>  $langues */
    public function coutPour(Transcription $transcription, array $langues): int
    {
        return count($this->aFacturer($transcription, $langues)) * $this->coutParLangue($transcription);
    }

    /**
     * @param  list<string>  $langues
     * @return array<string, AiOutput> sortie par langue
     *
     * @throws TraductionRefusee
     */
    public function lancer(User $utilisateur, Transcription $transcription, array $langues): array
    {
        if ($transcription->user_id !== $utilisateur->getKey() || $transcription->statut !== StatutTranscription::Termine) {
            throw new TraductionRefusee(TraductionRefusee::NON_TERMINEE);
        }

        $source = $this->langueSource($transcription);

        if (! in_array($source, (array) config('voxia.langues_forcables'), true)) {
            throw new TraductionRefusee(TraductionRefusee::SOURCE_NON_PRISE_EN_CHARGE);
        }

        $langues = $this->uniques($langues);

        if ($langues === []) {
            throw new TraductionRefusee(TraductionRefusee::AUCUNE_LANGUE);
        }

        foreach ($langues as $langue) {
            if ($langue === $source) {
                throw new TraductionRefusee(TraductionRefusee::MEME_LANGUE);
            }

            if (! in_array($langue, (array) config('voxia.ia.traduction.langues'), true)) {
                throw new TraductionRefusee(TraductionRefusee::LANGUE_INVALIDE);
            }
        }

        $segments = $this->segments->depuis($transcription);

        if ($segments === []) {
            throw new TraductionRefusee(TraductionRefusee::TRANSCRIPTION_VIDE);
        }

        $facturer = $this->aFacturer($transcription, $langues);
        $coutParLangue = $this->coutParLangue($transcription);
        $cout = count($facturer) * $coutParLangue;

        // Vérifié pour l'ensemble des langues avant la première écriture : un solde insuffisant ne lance rien.
        if ($cout > 0 && ! $this->quotas->creditsIaSuffisants($utilisateur, $cout)) {
            throw new TraductionRefusee(TraductionRefusee::CREDIT_INSUFFISANT, $cout);
        }

        $plan = $this->droits->planDe($utilisateur);
        $sorties = [];
        $aLancer = [];

        DB::transaction(function () use ($transcription, $langues, $facturer, $coutParLangue, $source, $segments, &$sorties, &$aLancer) {
            foreach ($langues as $langue) {
                if (! in_array($langue, $facturer, true)) {
                    $sorties[$langue] = $this->existante($transcription, $langue);

                    continue;
                }

                $sortie = AiOutput::updateOrCreate(
                    ['transcription_id' => $transcription->getKey(), 'fonctionnalite' => 'traduction', 'format' => $langue],
                    [
                        'statut' => StatutSortieIa::EnAttente,
                        'langue' => $langue,
                        'parametres' => ['source' => $source],
                        'credits' => $coutParLangue,
                        'contenu' => ['langue' => $langue, 'segments' => $segments, 'avertissements' => 0],
                        'texte_recherche' => null,
                        'date_debut' => null,
                        'date_termine' => null,
                        'date_echec' => null,
                    ],
                );

                $sorties[$langue] = $sortie;
                $aLancer[] = $sortie->getKey();
            }
        });

        // Mise en file après la transaction : la tâche ne doit pas voir une sortie non encore enregistrée.
        foreach ($aLancer as $sortieId) {
            GenererTraduction::dispatch($sortieId)->onQueue($this->droits->fileDe($plan));
        }

        return $sorties;
    }

    /**
     * @param  list<string>  $langues
     * @return list<string>
     */
    private function uniques(array $langues): array
    {
        return array_values(array_unique(array_map('strval', $langues)));
    }

    private function existante(Transcription $transcription, string $langue): ?AiOutput
    {
        return AiOutput::query()
            ->where('transcription_id', $transcription->getKey())
            ->where('fonctionnalite', 'traduction')
            ->where('format', $langue)
            ->first();
    }
}
