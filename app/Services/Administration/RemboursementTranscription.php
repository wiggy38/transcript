<?php

namespace App\Services\Administration;

use App\Enums\ActionAdministration;
use App\Enums\StatutTranscription;
use App\Exceptions\ActionAdministrationRefusee;
use App\Models\JournalAdministration;
use App\Models\Transcription;
use App\Models\UsageRecord;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Rembourse un traitement raté : chaque consommation encore active de la transcription reçoit une
 * ligne inverse (UsageRecord::reverse), dans une seule transaction, puis l'action est journalisée.
 */
class RemboursementTranscription
{
    /**
     * @return int nombre de lignes inversées
     *
     * @throws ActionAdministrationRefusee
     */
    public function rembourser(Transcription $transcription, User $administrateur, string $motif): int
    {
        $motif = trim($motif);

        if ($motif === '') {
            throw new ActionAdministrationRefusee(ActionAdministrationRefusee::MOTIF_VIDE);
        }

        return DB::transaction(function () use ($transcription, $administrateur, $motif) {
            $transcription = Transcription::query()->whereKey($transcription->getKey())->lockForUpdate()->firstOrFail();

            if ($transcription->statut !== StatutTranscription::Echec) {
                throw new ActionAdministrationRefusee(ActionAdministrationRefusee::PAS_EN_ECHEC);
            }

            $lignes = UsageRecord::query()
                ->where('transcription_id', $transcription->getKey())
                ->whereNull('annule_usage_record_id')
                ->whereDoesntHave('remboursement')
                ->orderBy('id')
                ->get();

            if ($lignes->isEmpty()) {
                throw new ActionAdministrationRefusee(ActionAdministrationRefusee::RIEN_A_REMBOURSER);
            }

            foreach ($lignes as $ligne) {
                $ligne->reverse('remboursement_echec');
            }

            JournalAdministration::create([
                'administrateur_id' => $administrateur->getKey(),
                'user_id' => $transcription->user_id,
                'transcription_id' => $transcription->getKey(),
                'action' => ActionAdministration::Remboursement,
                'motif' => $motif,
                'details' => [
                    'usage_record_ids' => $lignes->pluck('id')->all(),
                    'quantite_inversee' => $lignes->sum('quantite'),
                ],
            ]);

            return $lignes->count();
        });
    }
}
