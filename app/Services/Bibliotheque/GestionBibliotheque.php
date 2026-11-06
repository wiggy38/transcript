<?php

namespace App\Services\Bibliotheque;

use App\Enums\TypeEtiquette;
use App\Models\Dossier;
use App\Models\Etiquette;
use App\Models\Transcription;
use App\Models\User;
use App\Support\Libelle;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Dossiers et étiquettes de la bibliothèque (V2) : création, renommage, suppression.
 *
 * Un nom est unique par compte (dossiers) ou par compte et type (étiquettes), sans tenir compte de la casse.
 * Supprimer un dossier ne supprime aucune transcription : elles reviennent dans « Non classé ».
 * Les droits de plan sont contrôlés par les Policies (DossierPolicy, EtiquettePolicy) avant l'appel.
 */
class GestionBibliotheque
{
    public function creerDossier(User $utilisateur, string $nom): Dossier
    {
        $normalise = Libelle::normaliser($nom);

        if ($utilisateur->dossiers()->where('nom_normalise', $normalise)->exists()) {
            throw ValidationException::withMessages(['nom' => __('bibliotheque.erreurs.dossier_existe')]);
        }

        return $utilisateur->dossiers()->create([
            'nom' => $nom,
            'nom_normalise' => $normalise,
        ]);
    }

    public function renommerDossier(Dossier $dossier, string $nom): Dossier
    {
        $normalise = Libelle::normaliser($nom);

        $existe = Dossier::query()
            ->where('user_id', $dossier->user_id)
            ->where('nom_normalise', $normalise)
            ->whereKeyNot($dossier->getKey())
            ->exists();

        if ($existe) {
            throw ValidationException::withMessages(['nom' => __('bibliotheque.erreurs.dossier_existe')]);
        }

        $dossier->forceFill(['nom' => $nom, 'nom_normalise' => $normalise])->save();

        return $dossier;
    }

    /** Les transcriptions du dossier repassent en « Non classé » dans la même transaction. */
    public function supprimerDossier(Dossier $dossier): void
    {
        DB::transaction(function () use ($dossier) {
            Transcription::query()->where('dossier_id', $dossier->getKey())->update(['dossier_id' => null]);
            $dossier->delete();
        });
    }

    /**
     * Étiquette existante du même type et du même nom (sans tenir compte de la casse), sinon nouvelle.
     * C'est ce qui sert l'autocomplétion : saisir « Nike » deux fois ne crée pas deux étiquettes.
     */
    public function trouverOuCreerEtiquette(User $utilisateur, TypeEtiquette $type, string $nom): Etiquette
    {
        return $utilisateur->etiquettes()->firstOrCreate(
            ['type' => $type->value, 'nom_normalise' => Libelle::normaliser($nom)],
            ['nom' => $nom],
        );
    }

    public function renommerEtiquette(Etiquette $etiquette, TypeEtiquette $type, string $nom): Etiquette
    {
        $normalise = Libelle::normaliser($nom);

        $existe = Etiquette::query()
            ->where('user_id', $etiquette->user_id)
            ->where('type', $type->value)
            ->where('nom_normalise', $normalise)
            ->whereKeyNot($etiquette->getKey())
            ->exists();

        if ($existe) {
            throw ValidationException::withMessages(['nom' => __('bibliotheque.erreurs.etiquette_existe')]);
        }

        $etiquette->forceFill(['type' => $type, 'nom' => $nom, 'nom_normalise' => $normalise])->save();

        return $etiquette;
    }

    public function supprimerEtiquette(Etiquette $etiquette): void
    {
        DB::transaction(function () use ($etiquette) {
            $etiquette->transcriptions()->detach();
            $etiquette->delete();
        });
    }
}
