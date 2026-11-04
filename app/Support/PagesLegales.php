<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Valeurs reprises dans les pages publiques et les textes juridiques (remplacements « :nom »).
 * Tout vient de config/voxia.php ; ce service ne met aucun chiffre en dur.
 */
final class PagesLegales
{
    /** @return array<string, string> */
    public static function remplacements(): array
    {
        $plans = config('voxia.plans');
        $historique = (string) $plans['free']['history_days'];
        $email = config('voxia.contact.email');

        return [
            'editeur' => (string) config('voxia.editeur.nom'),
            'siret' => (string) config('voxia.editeur.siret'),
            'adresse' => (string) config('voxia.editeur.adresse'),
            'forme' => __('pages.forme', ['statut' => __('pages.a_confirmer')]),
            'email' => filled($email) ? (string) $email : __('pages.email_manquant'),
            'prestataire' => ucfirst((string) config('voxia.paiement.fournisseur')),
            'a_confirmer' => __('pages.a_confirmer'),
            'a_completer' => __('pages.a_completer'),
            'historique' => $historique,
            'jours' => $historique,
            'avant' => (string) $plans['free']['history_reminder_days_before'],
            'medias' => (string) config('voxia.retention.media_days'),
            'export' => (string) config('voxia.donnees.conservation_heures'),
            'minutes' => (string) intdiv((int) $plans['free']['max_video_seconds'], 60),
            'nombre' => (string) $plans['free']['videos_per_month'],
            'mois' => (string) $plans['pack']['validity_months'],
            'formats' => strtoupper(implode(', ', (array) config('voxia.uploads.extensions'))),
            'langues' => collect((array) config('voxia.langues_forcables'))
                ->map(fn (string $code) => __('accueil.langues.'.$code))
                ->implode(', '),
            'date' => self::dateBrouillon(),
        ];
    }

    /** Date du brouillon, en toutes lettres dans la langue courante. */
    public static function dateBrouillon(): string
    {
        $date = CarbonImmutable::parse((string) config('voxia.editeur.date_brouillon'));

        return Formats::date($date);
    }
}
