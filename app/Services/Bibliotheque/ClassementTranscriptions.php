<?php

namespace App\Services\Bibliotheque;

use App\Models\Dossier;
use App\Models\Etiquette;
use App\Models\Transcription;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Classement des transcriptions (V2) : dossier (une transcription au plus) et étiquettes (plusieurs).
 *
 * Chaque identifiant est filtré sur le compte avant l'écriture : une liste qui contient une transcription
 * ou une étiquette d'un autre compte ne modifie que les siennes.
 */
class ClassementTranscriptions
{
    /**
     * Range les transcriptions données dans le dossier, ou dans « Non classé » si $dossier est nul.
     *
     * @param  list<int>  $identifiants
     */
    public function ranger(User $utilisateur, array $identifiants, ?Dossier $dossier): int
    {
        if ($identifiants === []) {
            return 0;
        }

        return Transcription::query()
            ->where('user_id', $utilisateur->getKey())
            ->whereIn('id', $identifiants)
            ->update(['dossier_id' => $dossier?->getKey()]);
    }

    /**
     * Ajoute les étiquettes données aux transcriptions données, sans retirer les autres.
     *
     * @param  list<int>  $identifiants
     * @param  list<int>  $etiquettes
     */
    public function etiqueter(User $utilisateur, array $identifiants, array $etiquettes): void
    {
        $etiquettes = $this->etiquettesDuCompte($utilisateur, $etiquettes);

        if ($identifiants === [] || $etiquettes === []) {
            return;
        }

        DB::transaction(function () use ($utilisateur, $identifiants, $etiquettes) {
            Transcription::query()
                ->where('user_id', $utilisateur->getKey())
                ->whereIn('id', $identifiants)
                ->get()
                ->each(fn (Transcription $transcription) => $transcription->etiquettes()->syncWithoutDetaching($etiquettes));
        });
    }

    /**
     * Remplace les étiquettes d'une transcription par celles données.
     *
     * @param  list<int>  $etiquettes
     */
    public function definirEtiquettes(Transcription $transcription, array $etiquettes): void
    {
        $transcription->etiquettes()->sync($this->etiquettesDuCompte($transcription->user, $etiquettes));
    }

    /**
     * @param  list<int>  $identifiants
     * @return list<int>
     */
    private function etiquettesDuCompte(User $utilisateur, array $identifiants): array
    {
        if ($identifiants === []) {
            return [];
        }

        return Etiquette::query()
            ->where('user_id', $utilisateur->getKey())
            ->whereIn('id', $identifiants)
            ->pluck('id')
            ->map(fn ($identifiant) => (int) $identifiant)
            ->all();
    }
}
