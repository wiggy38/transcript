<?php

// Aides partagées par les tests de la bibliothèque (V2). Préfixe « bib » : les noms sont globaux.
// Ce fichier ne se termine pas par « Test.php » : Pest ne l'exécute pas, les tests le chargent.

use App\Enums\Plan;
use App\Enums\TypeEtiquette;
use App\Models\Dossier;
use App\Models\Etiquette;
use App\Models\Transcription;
use App\Models\User;

/** Compte Pro, plan réel activé pour le test. */
function bibPro(): User
{
    return bibCompte(Plan::Pro);
}

/** Compte Gratuit, plan réel activé pour le test. */
function bibGratuit(): User
{
    return bibCompte(Plan::Free);
}

function bibCompte(Plan $plan): User
{
    config(['voxia.droits.plan_reel' => true]);

    $utilisateur = User::factory()->create(['locale' => 'fr']);
    $utilisateur->forceFill(['plan' => $plan])->save();
    app()->setLocale('fr');

    return $utilisateur;
}

/** Transcription terminée du compte. created_at et dossier_id ne sont pas assignables : on les pose à part. */
function bibTranscription(User $utilisateur, array $attributs = [], ?DateTimeInterface $date = null): Transcription
{
    $dossier = $attributs['dossier'] ?? null;
    unset($attributs['dossier']);

    $transcription = Transcription::factory()->termine()->for($utilisateur)->create(array_merge(
        ['titre' => 'Titre '.fake()->unique()->word()],
        $attributs,
    ));

    $ajustements = [];

    if ($date !== null) {
        $ajustements['created_at'] = $date;
    }

    if ($dossier instanceof Dossier) {
        $ajustements['dossier_id'] = $dossier->getKey();
    }

    if ($ajustements !== []) {
        $transcription->forceFill($ajustements)->save();
    }

    return $transcription->refresh();
}

function bibDossier(User $utilisateur, string $nom): Dossier
{
    return Dossier::factory()->for($utilisateur)->create([
        'nom' => $nom,
        'nom_normalise' => mb_strtolower($nom),
    ]);
}

function bibEtiquette(User $utilisateur, TypeEtiquette $type, string $nom): Etiquette
{
    return Etiquette::factory()->for($utilisateur)->create([
        'type' => $type,
        'nom' => $nom,
        'nom_normalise' => mb_strtolower($nom),
    ]);
}
