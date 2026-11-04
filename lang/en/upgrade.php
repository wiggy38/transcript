<?php

// Upgrade windows (cahier §2, upgrade path). Numbers and prices come from config/voxia.php.
return [
    'lignes' => [
        'videos' => ':nombre videos per month',
        'duree_minutes' => 'Up to :minutes minutes per video',
        'duree_heures' => 'Up to :heures h per video',
        'exports' => 'Exports: :formats',
        'heures_mois' => ':heures h of transcription per month',
        'heures_pack' => ':heures h of transcription, valid :mois months',
        'historique' => 'Unlimited history and search',
        'ia' => 'AI credits for script analysis',
    ],

    'periode' => [
        'mois' => '/ month',
        'unique' => 'one-time payment',
    ],

    'annuel' => 'or :prix per year, that is :mensuel per month',

    'actions' => [
        'ton_plan' => 'Your plan',
        'pro_mensuel' => 'Upgrade to Pro · :prix / month',
        'pro_annuel' => 'Or :prix / year',
        'pack' => 'Buy the Pack · :prix',
    ],

    'tarifs' => [
        'titre' => 'Pricing',
        'sous_titre' => 'Start for free. Upgrade to Pro when Voxia becomes a working tool for you.',
        'pied' => 'Prices in US dollars. VAT and invoices are handled by Paddle, our payment provider.',
    ],

    'quota' => [
        'titre' => 'You have used your videos for this month',
        'texte' => 'The Free plan includes :limite videos per month, and the counter resets on the 1st. Upgrade to Pro or the Pack to transcribe this video now.',
        'reprise' => 'Your link stays in the field: after payment, you come back here and can launch it.',
    ],

    'apercu' => [
        'titre' => 'Full transcription',
        'texte' => ':minutes minutes of this :duree video are not transcribed yet. Upgrade to Pro: your transcription will be run again in full as soon as the payment is confirmed.',
    ],

    'export' => [
        'titre' => ':format export',
        'texte' => 'The :format format is included from the :plan plan. Your transcription stays here.',
    ],

    'recherche' => [
        'titre' => 'Search your history',
        'texte' => 'Find a passage in all your transcriptions, by a single word. Search is reserved to Pro and the Pack.',
    ],

    'analyse' => [
        'titre' => 'Script analysis',
        'texte' => 'Your plan does not include AI credits. Pro includes some every month to analyse the structure of your scripts.',
    ],

    'reprise' => 'Your link is back in the field. Launch the transcription whenever you like.',
];
