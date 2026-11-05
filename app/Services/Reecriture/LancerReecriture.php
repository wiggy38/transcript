<?php

namespace App\Services\Reecriture;

use App\Enums\FormatReecriture;
use App\Enums\LongueurReecriture;
use App\Enums\StatutSortieIa;
use App\Enums\StatutTranscription;
use App\Enums\TonReecriture;
use App\Exceptions\ReecritureRefusee;
use App\Jobs\GenererReecriture;
use App\Models\AiOutput;
use App\Models\Transcription;
use App\Models\User;
use App\Services\Plans\PlanFeatures;
use App\Services\Quota\QuotaService;
use Illuminate\Support\Facades\DB;

/**
 * Lance la réécriture d'une transcription, dans un ou plusieurs formats. Toutes les règles sont vérifiées
 * avant toute écriture : si le solde ne couvre pas l'ensemble des formats facturés, rien n'est lancé.
 *
 * - Un format déjà terminé se rouvre sans coût ; le régénérer coûte 1 crédit et demande la confirmation.
 * - Un format déjà en cours n'est pas relancé ni facturé une seconde fois.
 * - Chaque format facturé est une sortie (ai_outputs) : 1 crédit à la réussite, rien à l'échec.
 */
final class LancerReecriture
{
    public function __construct(
        private readonly QuotaService $quotas,
        private readonly PlanFeatures $droits,
    ) {}

    /** Crédits IA par format (voxia.ia.reecriture.credits_par_format). */
    public function coutParFormat(): int
    {
        return (int) config('voxia.ia.reecriture.credits_par_format');
    }

    /**
     * Formats qui seraient facturés si on les lançait maintenant. Sert à afficher le coût avant le lancement.
     *
     * @param  list<FormatReecriture>  $formats
     * @return list<FormatReecriture>
     */
    public function aFacturer(Transcription $transcription, array $formats, bool $regenerer = false): array
    {
        $facturer = [];

        foreach ($this->uniques($formats) as $format) {
            $existante = $this->existante($transcription, $format);

            if ($existante?->statut?->enCours()) {
                continue;
            }

            if ($existante?->statut === StatutSortieIa::Termine && ! $regenerer) {
                continue;
            }

            $facturer[] = $format;
        }

        return $facturer;
    }

    public function coutPour(Transcription $transcription, array $formats, bool $regenerer = false): int
    {
        return count($this->aFacturer($transcription, $formats, $regenerer)) * $this->coutParFormat();
    }

    /**
     * @param  list<FormatReecriture>  $formats
     * @return array<string, AiOutput> sortie par format (clé : valeur du format)
     *
     * @throws ReecritureRefusee
     */
    public function lancer(
        User $utilisateur,
        Transcription $transcription,
        array $formats,
        TonReecriture $ton,
        LongueurReecriture $longueur,
        string $langue,
        bool $regenerer = false,
        bool $confirme = false,
    ): array {
        if ($transcription->user_id !== $utilisateur->getKey() || $transcription->statut !== StatutTranscription::Termine) {
            throw new ReecritureRefusee(ReecritureRefusee::NON_TERMINEE);
        }

        $plan = $this->droits->planDe($utilisateur);

        if ($this->droits->creditsIaMensuels($plan) <= 0) {
            throw new ReecritureRefusee(ReecritureRefusee::PLAN_SANS_ACCES);
        }

        if (! in_array($langue, (array) config('voxia.langues_forcables'), true)) {
            throw new ReecritureRefusee(ReecritureRefusee::LANGUE_INVALIDE);
        }

        $formats = $this->uniques($formats);

        if ($formats === []) {
            throw new ReecritureRefusee(ReecritureRefusee::AUCUN_FORMAT);
        }

        if ($regenerer && ! $confirme) {
            throw new ReecritureRefusee(ReecritureRefusee::CONFIRMATION_REQUISE);
        }

        $facturer = $this->aFacturer($transcription, $formats, $regenerer);
        $cout = count($facturer) * $this->coutParFormat();

        // Vérifié pour l'ensemble des formats avant la première écriture : un solde insuffisant ne lance rien.
        if ($cout > 0 && ! $this->quotas->creditsIaSuffisants($utilisateur, $cout)) {
            throw new ReecritureRefusee(ReecritureRefusee::CREDIT_INSUFFISANT, $cout);
        }

        $sorties = [];
        $aLancer = [];

        DB::transaction(function () use ($transcription, $formats, $facturer, $ton, $longueur, $langue, &$sorties, &$aLancer) {
            foreach ($formats as $format) {
                if (! in_array($format, $facturer, true)) {
                    $sorties[$format->value] = $this->existante($transcription, $format);

                    continue;
                }

                $sortie = AiOutput::updateOrCreate(
                    ['transcription_id' => $transcription->getKey(), 'fonctionnalite' => 'reecriture', 'format' => $format->value],
                    [
                        'statut' => StatutSortieIa::EnAttente,
                        'langue' => $langue,
                        'parametres' => ['ton' => $ton->value, 'longueur' => $longueur->value],
                        'credits' => $this->coutParFormat(),
                        'contenu' => null,
                        'date_debut' => null,
                        'date_termine' => null,
                        'date_echec' => null,
                    ],
                );

                $sorties[$format->value] = $sortie;
                $aLancer[] = $sortie->getKey();
            }
        });

        // Mise en file après la transaction : la tâche ne doit pas voir une sortie non encore enregistrée.
        foreach ($aLancer as $sortieId) {
            GenererReecriture::dispatch($sortieId)->onQueue($this->droits->fileDe($plan));
        }

        return $sorties;
    }

    /**
     * Formats sans doublon, dans l'ordre de la demande. (array_unique ne sait pas comparer des enums.)
     *
     * @param  list<FormatReecriture>  $formats
     * @return list<FormatReecriture>
     */
    private function uniques(array $formats): array
    {
        $vus = [];

        foreach ($formats as $format) {
            $vus[$format->value] = $format;
        }

        return array_values($vus);
    }

    private function existante(Transcription $transcription, FormatReecriture $format): ?AiOutput
    {
        return AiOutput::query()
            ->where('transcription_id', $transcription->getKey())
            ->where('fonctionnalite', 'reecriture')
            ->where('format', $format->value)
            ->first();
    }
}
