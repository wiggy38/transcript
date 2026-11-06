<?php

return [

    'titre' => 'Traduire',

    'sous_titre' => 'Traduis la transcription segment par segment. Les horodatages restent ceux de l’original.',

    'langues_legende' => 'Langues cibles',

    'cout' => 'Coût : :credits crédit(s) IA (1 par heure entamée et par langue). Rouvrir une langue déjà traduite est gratuit.',

    'bouton' => 'Traduire',

    'plan_sans_acces' => 'Ton plan ne donne pas accès à la traduction. Passe à Pro ou Business pour traduire.',

    'aucune_langue' => 'Choisis au moins une langue.',

    'refus' => [
        'non_terminee' => 'La transcription n’est pas terminée.',
        'transcription_vide' => 'Cette transcription ne contient pas de texte à traduire.',
        'source_non_prise_en_charge' => 'La langue de cette transcription n’est pas prise en charge pour la traduction.',
        'langue_invalide' => 'Cette langue n’est pas proposée pour la traduction.',
        'meme_langue' => 'La langue cible est la langue de la transcription.',
        'aucune_langue' => 'Choisis au moins une langue.',
        'credit_ia_insuffisant' => 'Crédits IA insuffisants pour :credits crédit(s). Aucune langue n’a été lancée.',
    ],

    'statut' => [
        'en_attente' => 'En file d’attente…',
        'en_cours' => 'Traduction en cours…',
        'termine' => 'Traduite',
        'echec' => 'Échec. Aucun crédit n’a été consommé.',
    ],

    'langues' => [
        'fr' => 'Français',
        'en' => 'Anglais',
        'es' => 'Espagnol',
        'de' => 'Allemand',
        'it' => 'Italien',
        'pt' => 'Portugais',
    ],

    'selecteur' => 'Langue affichée',

    'original' => 'Original',

    'col_original' => 'Original',

    'col_traduction' => 'Traduction',

    'lire_a' => 'Lire à :temps',

    'trop_long' => 'Trop long pour sa durée',

    'avertissements' => ':nombre segment(s) dépassent la durée prévue. Le texte est affiché en entier, mais ces sous-titres ne respectent pas la règle d’une seconde minimum.',

    'vide' => 'Lance une traduction pour afficher la version côte à côte.',

    'exports' => [
        'titre' => 'Télécharger la traduction',
        'horodatages' => 'Avec horodatages',
        'sans_horodatages' => 'Sans horodatages',
        'formats' => [
            'txt' => 'TXT',
            'pdf' => 'PDF',
            'srt' => 'SRT',
            'vtt' => 'VTT',
            'docx' => 'DOCX',
            'json' => 'JSON',
        ],
    ],
];
