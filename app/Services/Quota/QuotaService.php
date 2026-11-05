<?php

namespace App\Services\Quota;

use App\Enums\MotifEchec;
use App\Enums\Plan;
use App\Enums\StatutAnalyseScript;
use App\Enums\StatutTranscription;
use App\Enums\UniteConsommation;
use App\Exceptions\AnalyseRefusee;
use App\Exceptions\ErreurVideo;
use App\Models\PackAchat;
use App\Models\ScriptAnalysis;
use App\Models\Transcription;
use App\Models\UsageRecord;
use App\Models\User;
use App\Services\Plans\PlanFeatures;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Quotas des plans (cahier §5) : vidéos par mois (Gratuit), secondes par mois (Pro), Packs, crédits IA.
 *
 * Règles :
 * - Gratuit : une vidéo traitée = 1 vidéo, quelle que soit sa durée ; compteur remis à zéro le 1er du mois (UTC).
 * - Pro : durée réelle arrondie à la minute supérieure ; compteur remis à zéro à la date anniversaire de l'abonnement.
 *   Une vidéo consomme d'abord le Pro, le reste éventuel vient des Packs.
 * - Pack : 10 h valables 6 mois à partir de l'achat ; plusieurs Packs s'additionnent, le plus ancien est consommé d'abord.
 * - Crédits IA : mensuels, Pro seulement, décomptés à part des secondes.
 * - Un décompte n'est écrit que pour une transcription réussie, dans la transaction qui la passe à « Terminé ».
 * - Les transcriptions en cours comptent comme réservées (durée connue) : deux lancements simultanés ne
 *   dépassent pas le quota. Le décompte final est refait sous verrou sur le compte : il ne peut jamais dépasser.
 *
 * Toute écriture passe par une transaction avec un verrou sur la ligne de l'utilisateur (lockForUpdate).
 */
class QuotaService
{
    /** Besoin d'un lancement dont la durée n'est pas encore connue (fichier avant ffprobe, lien sans aperçu). */
    public const SECONDES_MINIMUM_LANCEMENT = 60;

    public function __construct(private readonly PlanFeatures $droits) {}

    /**
     * Lancement : refuse avant toute création si le quota ne couvre pas la vidéo, ou si la vidéo dépasse
     * la durée max du plan payant. À appeler dans la même transaction que la création : le verrou sur le
     * compte garde la vérification et la création ensemble.
     *
     * @throws ErreurVideo motif « trop_longue » ou « quota_epuise »
     */
    public function verifier(User $utilisateur, ?int $dureeSecondes = null): void
    {
        DB::transaction(function () use ($utilisateur, $dureeSecondes) {
            $utilisateur = $this->verrouiller($utilisateur);
            $plan = $this->droits->planDe($utilisateur);

            if (! $this->droits->estGratuit($plan) && $dureeSecondes !== null && $dureeSecondes > $this->droits->dureeMaxSecondes($plan)) {
                throw new ErreurVideo(MotifEchec::TropLongue, $dureeSecondes.' s pour un maximum de '.$this->droits->dureeMaxSecondes($plan).' s');
            }

            $this->verifierPlan($utilisateur, $plan, $dureeSecondes, null);
        });
    }

    /**
     * Au traitement, la durée réelle est connue : refuse avant la transcription (aucun décompte).
     * La transcription elle-même n'est pas comptée parmi les réservations.
     *
     * @throws ErreurVideo motif « quota_epuise »
     */
    public function verifierTraitement(Transcription $transcription): void
    {
        $transcription->loadMissing('user');

        DB::transaction(function () use ($transcription) {
            $utilisateur = $this->verrouiller($transcription->user);

            $this->verifierPlan(
                $utilisateur,
                $this->droits->planDe($utilisateur),
                $transcription->duree_secondes,
                $transcription,
            );
        });
    }

    /**
     * Décompte d'une transcription réussie. Appelé dans la transaction qui passe la transcription à
     * « Terminé » : en cas d'échec ici, rien n'est écrit et la transcription reste en échec.
     *
     * @throws ErreurVideo motif « quota_epuise » si le quota ne couvre plus la vidéo
     */
    public function consommerTranscription(Transcription $transcription): void
    {
        $transcription->loadMissing('user');

        $utilisateur = $this->verrouiller($transcription->user);
        $plan = $this->droits->planDe($utilisateur);
        $periode = $this->periode($utilisateur, $plan);
        $base = [
            'user_id' => $utilisateur->getKey(),
            'transcription_id' => $transcription->getKey(),
            'motif' => 'transcription',
        ];

        if ($this->droits->estGratuit($plan)) {
            $limite = (int) $this->droits->videosMensuelles($plan);

            if ($this->consommee($utilisateur, $plan, UniteConsommation::Video, $periode) + 1 > $limite) {
                throw new ErreurVideo(MotifEchec::QuotaEpuise, 'vidéos du mois épuisées');
            }

            UsageRecord::create($base + [
                'unite' => UniteConsommation::Video,
                'plan' => $plan,
                'periode' => $periode,
                'quantite' => 1,
            ]);

            return;
        }

        if ($transcription->duree_secondes === null) {
            throw new LogicException('Décompte impossible : durée inconnue.');
        }

        $facturee = $this->facturee((int) $transcription->duree_secondes);

        // Le Pro d'abord, puis les Packs du plus ancien au plus récent.
        $duMois = min($facturee, $this->resteMensuel($utilisateur, $plan, $periode));
        $reste = $facturee - $duMois;

        $lignes = [];

        if ($duMois > 0) {
            $lignes[] = ['plan' => $plan, 'periode' => $periode, 'pack_achat_id' => null, 'quantite' => $duMois];
        }

        foreach ($this->packsValides($utilisateur) as $pack) {
            if ($reste <= 0) {
                break;
            }

            $pris = min($reste, $pack->restantes);

            if ($pris > 0) {
                $lignes[] = ['plan' => Plan::Pack, 'periode' => null, 'pack_achat_id' => $pack->getKey(), 'quantite' => $pris];
                $reste -= $pris;
            }
        }

        if ($reste > 0) {
            throw new ErreurVideo(MotifEchec::QuotaEpuise, 'secondes insuffisantes au décompte');
        }

        foreach ($lignes as $ligne) {
            UsageRecord::create($base + $ligne + ['unite' => UniteConsommation::Secondes]);
        }
    }

    /**
     * Point d'accroche des crédits IA, appelé avant de lancer une analyse de script. Refusé si le plan
     * n'a pas de crédits (Gratuit, Pack) ou si les crédits restants, moins ceux des analyses en cours, ne suffisent pas.
     *
     * @throws AnalyseRefusee motif « credit_ia_insuffisant »
     */
    public function verifierCreditIa(User $utilisateur): void
    {
        DB::transaction(function () use ($utilisateur) {
            $utilisateur = $this->verrouiller($utilisateur);
            $plan = $this->droits->planDe($utilisateur);
            $mensuel = $this->droits->creditsIaMensuels($plan);
            $cout = $this->droits->creditsParAnalyse();

            if ($mensuel <= 0) {
                throw new AnalyseRefusee(AnalyseRefusee::CREDIT_IA_INSUFFISANT);
            }

            $utilises = $this->consommee($utilisateur, $plan, UniteConsommation::CreditIa, $this->periode($utilisateur, $plan));

            $enCours = ScriptAnalysis::query()
                ->whereIn('statut', [StatutAnalyseScript::EnAttente, StatutAnalyseScript::Analyse])
                ->whereHas('transcription', fn ($requete) => $requete->where('user_id', $utilisateur->getKey()))
                ->count();

            if ($mensuel - $utilises - $enCours * $cout < $cout) {
                throw new AnalyseRefusee(AnalyseRefusee::CREDIT_IA_INSUFFISANT);
            }
        });
    }

    /** Décompte d'une analyse réussie : une ligne au journal, jamais pour un échec. */
    public function consommerCreditIa(User $utilisateur, ScriptAnalysis $analyse): UsageRecord
    {
        $plan = $this->droits->planDe($utilisateur);

        return UsageRecord::create([
            'user_id' => $utilisateur->getKey(),
            'transcription_id' => $analyse->transcription_id,
            'script_analysis_id' => $analyse->getKey(),
            'unite' => UniteConsommation::CreditIa,
            'plan' => $plan,
            'periode' => $this->periode($utilisateur, $plan),
            'quantite' => $this->droits->creditsParAnalyse(),
            'motif' => 'analyse_script',
        ]);
    }

    /**
     * Ce qui reste à l'utilisateur, pour le compteur affiché dans la mise en page. Les valeurs
     * sont brutes (entiers) ; l'affichage choisit les mots.
     *
     * @return array{plan: Plan, videos_utilisees?: int, videos_limite?: int, secondes_mois?: ?int, secondes_packs?: int, credits_ia?: ?int, credits_ia_limite?: int}
     */
    public function resume(User $utilisateur): array
    {
        $plan = $this->droits->planDe($utilisateur);
        $periode = $this->periode($utilisateur, $plan);

        if ($this->droits->estGratuit($plan)) {
            return [
                'plan' => $plan,
                'videos_utilisees' => $this->consommee($utilisateur, $plan, UniteConsommation::Video, $periode),
                'videos_limite' => (int) $this->droits->videosMensuelles($plan),
            ];
        }

        $creditsIa = $this->droits->creditsIaMensuels($plan);

        return [
            'plan' => $plan,
            'secondes_mois' => $this->droits->secondesMensuelles($plan) !== null ? $this->resteMensuel($utilisateur, $plan, $periode) : null,
            'secondes_packs' => $this->resteDesPacks($utilisateur),
            'credits_ia' => $creditsIa > 0 ? max(0, $creditsIa - $this->consommee($utilisateur, $plan, UniteConsommation::CreditIa, $periode)) : null,
            'credits_ia_limite' => $creditsIa,
        ];
    }

    /**
     * Début du cycle en cours du plan : le 1er du mois (UTC) pour le Gratuit, la dernière date
     * anniversaire de l'abonnement pour les autres plans.
     */
    public function periode(User $utilisateur, Plan $plan, ?CarbonInterface $maintenant = null): string
    {
        $maintenant = CarbonImmutable::instance($maintenant ?? now())->utc();

        if ($this->droits->estGratuit($plan)) {
            return $maintenant->startOfMonth()->toDateString();
        }

        return $this->debutCycleAnniversaire($utilisateur, $maintenant)->toDateString();
    }

    /**
     * Dernière date anniversaire de l'abonnement, en UTC. Une ancre le 31 donne le dernier jour des
     * mois courts. Sans date d'abonnement, l'ancre est la création du compte (à confirmer, voir avancement).
     */
    public function debutCycleAnniversaire(User $utilisateur, CarbonInterface $maintenant): CarbonImmutable
    {
        $maintenant = CarbonImmutable::instance($maintenant)->utc();
        $ancre = CarbonImmutable::instance($utilisateur->abonnement_debut_at ?? $utilisateur->created_at ?? $maintenant)->utc();

        if ($ancre->greaterThan($maintenant)) {
            return $ancre;
        }

        $mois = (int) $ancre->diffInMonths($maintenant);

        while ($ancre->addMonthsNoOverflow($mois + 1)->lessThanOrEqualTo($maintenant)) {
            $mois++;
        }

        while ($mois > 0 && $ancre->addMonthsNoOverflow($mois)->greaterThan($maintenant)) {
            $mois--;
        }

        return $ancre->addMonthsNoOverflow($mois);
    }

    /**
     * Refait la vérification sous verrou, au lancement comme au traitement.
     *
     * @throws ErreurVideo motif « quota_epuise »
     */
    private function verifierPlan(User $utilisateur, Plan $plan, ?int $duree, ?Transcription $exclue): void
    {
        $enCours = $this->transcriptionsEnCours($utilisateur, $exclue);

        if ($this->droits->estGratuit($plan)) {
            $utilisees = $this->consommee($utilisateur, $plan, UniteConsommation::Video, $this->periode($utilisateur, $plan));
            $limite = (int) $this->droits->videosMensuelles($plan);

            if ($utilisees + $enCours->count() + 1 > $limite) {
                throw new ErreurVideo(MotifEchec::QuotaEpuise, 'vidéos du mois épuisées');
            }

            return;
        }

        $reservees = $enCours->sum(fn (Transcription $t) => $t->duree_secondes !== null ? $this->facturee((int) $t->duree_secondes) : 0);
        $besoin = $duree !== null ? $this->facturee($duree) : self::SECONDES_MINIMUM_LANCEMENT;
        $disponibles = $this->resteMensuel($utilisateur, $plan, $this->periode($utilisateur, $plan)) + $this->resteDesPacks($utilisateur);

        if ($disponibles - $reservees < $besoin) {
            throw new ErreurVideo(MotifEchec::QuotaEpuise, 'secondes insuffisantes');
        }
    }

    /** Secondes du mois encore disponibles pour ce plan (0 si le plan n'a pas de volume mensuel). */
    private function resteMensuel(User $utilisateur, Plan $plan, string $periode): int
    {
        $volume = $this->droits->secondesMensuelles($plan);

        if ($volume === null) {
            return 0;
        }

        return max(0, $volume - $this->consommee($utilisateur, $plan, UniteConsommation::Secondes, $periode));
    }

    /**
     * Packs valables (non expirés), du plus ancien au plus récent. Chacun porte « restantes ».
     *
     * @return list<object{id: int, restantes: int}>
     */
    private function packsValides(User $utilisateur): array
    {
        $packs = PackAchat::query()
            ->where('user_id', $utilisateur->getKey())
            ->where('expire_at', '>', now())
            ->orderBy('expire_at')
            ->orderBy('id')
            ->withSum('usageRecords as consommees', 'quantite')
            ->get();

        return $packs->map(function (PackAchat $pack) {
            $pack->restantes = max(0, $pack->secondes - (int) $pack->consommees);

            return $pack;
        })->filter(fn (PackAchat $pack) => $pack->restantes > 0)->values()->all();
    }

    private function resteDesPacks(User $utilisateur): int
    {
        return array_sum(array_map(fn (PackAchat $pack) => $pack->restantes, $this->packsValides($utilisateur)));
    }

    /** Transcriptions réservées : en cours, sauf celle qu'on vérifie. */
    private function transcriptionsEnCours(User $utilisateur, ?Transcription $exclue)
    {
        return Transcription::query()
            ->where('user_id', $utilisateur->getKey())
            ->whereIn('statut', [StatutTranscription::EnAttente, StatutTranscription::Telechargement, StatutTranscription::Transcription])
            ->when($exclue !== null, fn ($requete) => $requete->whereKeyNot($exclue->getKey()))
            ->get(['id', 'duree_secondes']);
    }

    private function consommee(User $utilisateur, Plan $plan, UniteConsommation $unite, string $periode): int
    {
        return (int) UsageRecord::query()
            ->where('user_id', $utilisateur->getKey())
            ->where('unite', $unite->value)
            ->where('plan', $plan->value)
            ->where('periode', $periode)
            ->sum('quantite');
    }

    /** Durée facturée : la minute supérieure (offre). 0 s reste 0 s. */
    private function facturee(int $secondes): int
    {
        return (int) (ceil($secondes / PlanFeatures::SECONDES_PAR_MINUTE) * PlanFeatures::SECONDES_PAR_MINUTE);
    }

    /** Relit le compte sous verrou (lockForUpdate), dans la transaction de l'appelant. */
    private function verrouiller(User $utilisateur): User
    {
        return User::query()->whereKey($utilisateur->getKey())->lockForUpdate()->firstOrFail();
    }
}
