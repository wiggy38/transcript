<?php

namespace App\Services\Administration;

use App\Enums\EtapeTraitement;
use App\Enums\StatutTranscription;
use App\Models\ProcessingLog;
use App\Models\Transcription;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Chiffres du tableau de bord (cahier §5). Chaque méthode répond sur la fenêtre passée en paramètre ;
 * les administrateurs sont exclus des inscriptions.
 */
class IndicateursAdministration
{
    /** Traitements lancés depuis minuit (fuseau de l'application). */
    public function traitementsDuJour(): int
    {
        return Transcription::query()
            ->where('created_at', '>=', now()->startOfDay())
            ->count();
    }

    /**
     * Tentatives de téléchargement (étape « telechargement » du journal) et échecs depuis la date.
     *
     * @return array{tentatives: int, echecs: int, taux: ?float} taux entre 0 et 1, null sans tentative
     */
    public function tauxEchecTelechargement(CarbonInterface $depuis): array
    {
        $base = ProcessingLog::query()
            ->where('etape', EtapeTraitement::Telechargement)
            ->where('created_at', '>=', $depuis);

        $tentatives = (clone $base)->count();
        $echecs = (clone $base)->where('succes', false)->count();

        return [
            'tentatives' => $tentatives,
            'echecs' => $echecs,
            'taux' => $tentatives > 0 ? $echecs / $tentatives : null,
        ];
    }

    /**
     * Coût estimé moyen par heure de vidéo, sur les transcriptions terminées depuis la date.
     * Null si aucune durée n'est connue.
     */
    public function coutMoyenParHeureVideo(CarbonInterface $depuis): ?float
    {
        $ligne = Transcription::query()
            ->where('statut', StatutTranscription::Termine)
            ->whereNotNull('duree_secondes')
            ->where('duree_secondes', '>', 0)
            ->where('created_at', '>=', $depuis)
            ->selectRaw('COALESCE(SUM(cout_estime_usd), 0) AS cout, SUM(duree_secondes) AS secondes')
            ->first();

        if ($ligne === null || (int) $ligne->secondes === 0) {
            return null;
        }

        return (float) $ligne->cout / ((int) $ligne->secondes / 3600);
    }

    /** Comptes créés depuis la date, hors administrateurs. */
    public function inscriptions(CarbonInterface $depuis): int
    {
        return User::query()
            ->where('is_admin', false)
            ->where('created_at', '>=', $depuis)
            ->count();
    }

    /**
     * Conversions Gratuit vers payant : comptes dont le premier achat (abonnement Paddle ou Pack)
     * tombe depuis la date. Un compte déjà payant avant la date n'est pas compté.
     */
    public function conversions(CarbonInterface $depuis): int
    {
        $abonnements = DB::table('subscriptions')
            ->where('billable_type', (new User)->getMorphClass())
            ->selectRaw('billable_id AS user_id, created_at AS achat_at');

        $packs = DB::table('pack_achats')
            ->selectRaw('user_id, achete_at AS achat_at');

        $premiers = DB::query()
            ->fromSub($abonnements->unionAll($packs), 'achats')
            ->selectRaw('user_id, MIN(achat_at) AS premier_achat')
            ->groupBy('user_id');

        return DB::query()
            ->fromSub($premiers, 'premiers')
            ->where('premier_achat', '>=', $depuis)
            ->count();
    }
}
