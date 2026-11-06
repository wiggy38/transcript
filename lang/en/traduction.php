<?php

return [

    'titre' => 'Translate',

    'sous_titre' => 'Translates the transcription segment by segment. Timestamps stay those of the original.',

    'langues_legende' => 'Target languages',

    'cout' => 'Cost: :credits AI credit(s) (1 per started hour and per language). Reopening a language already translated is free.',

    'bouton' => 'Translate',

    'plan_sans_acces' => 'Your plan does not include translation. Upgrade to Pro or Business to translate.',

    'aucune_langue' => 'Choose at least one language.',

    'refus' => [
        'non_terminee' => 'The transcription is not finished.',
        'transcription_vide' => 'This transcription has no text to translate.',
        'source_non_prise_en_charge' => 'The language of this transcription is not supported for translation.',
        'langue_invalide' => 'This language is not offered for translation.',
        'meme_langue' => 'The target language is the transcription’s language.',
        'aucune_langue' => 'Choose at least one language.',
        'credit_ia_insuffisant' => 'Not enough AI credits for :credits credit(s). No language was started.',
    ],

    'statut' => [
        'en_attente' => 'Queued…',
        'en_cours' => 'Translating…',
        'termine' => 'Translated',
        'echec' => 'Failed. No credit was used.',
    ],

    'langues' => [
        'fr' => 'French',
        'en' => 'English',
        'es' => 'Spanish',
        'de' => 'German',
        'it' => 'Italian',
        'pt' => 'Portuguese',
    ],

    'selecteur' => 'Language shown',

    'original' => 'Original',

    'col_original' => 'Original',

    'col_traduction' => 'Translation',

    'lire_a' => 'Play from :temps',

    'trop_long' => 'Too long for its duration',

    'avertissements' => ':nombre segment(s) exceed the planned duration. The full text is shown, but these subtitles do not meet the one-second minimum.',

    'vide' => 'Start a translation to show the side-by-side view.',

    'exports' => [
        'titre' => 'Download the translation',
        'horodatages' => 'With timestamps',
        'sans_horodatages' => 'Without timestamps',
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
