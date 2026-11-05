<?php

return [

    'titre' => 'Rewrite',

    'sous_titre' => 'Turn the video into ready-to-publish content. Each format costs 1 AI credit.',

    'formats_legende' => 'Formats to generate',

    'formats' => [
        'article' => 'Blog article',
        'newsletter' => 'Newsletter',
        'linkedin' => 'LinkedIn post',
        'thread' => 'X thread',
    ],

    'ton' => 'Tone',

    'tons' => [
        'professionnel' => 'Professional',
        'conversationnel' => 'Conversational',
        'enthousiaste' => 'Enthusiastic',
    ],

    'longueur' => 'Article length',

    'longueurs' => [
        'courte' => 'Short',
        'moyenne' => 'Medium',
        'longue' => 'Long',
    ],

    'aide_longueur' => 'Applies to the article only.',

    'langue' => 'Output language',

    'bouton' => 'Rewrite',

    'cout' => 'Cost: :credits AI credit(s) (:unitaire per format). Reopening a format already generated is free.',

    'plan_sans_acces' => 'Rewriting is included in the Pro and Business plans.',

    'onglets' => 'Generated content',

    'statut' => [
        'en_attente' => 'Rewrite queued…',
        'en_cours' => 'Rewrite in progress…',
        'termine' => 'Rewrite complete.',
        'echec' => 'The rewrite failed. No credit was used.',
    ],

    'copier' => 'Copy',

    'copie' => 'Copied',

    'copier_bloc' => 'Copy: :bloc',

    'blocs' => [
        'titre' => 'the title',
        'introduction' => 'the introduction',
        'corps' => 'the body',
        'post' => 'the post',
        'message' => 'the message',
    ],

    'compteur_linkedin' => ':caracteres characters out of :max.',

    'exporter' => 'Export as',

    'verrou' => 'Available with the :plan plan',

    'regenerer' => 'Regenerate this format',

    'confirmer_regeneration' => 'Regenerating this format costs :credits AI credit(s). Continue?',

    'confirmer' => 'Confirm',

    'annuler' => 'Cancel',

    'refus' => [
        'non_terminee' => 'The transcription must be complete before it can be rewritten.',
        'plan_sans_acces' => 'Rewriting is included in the Pro and Business plans.',
        'credit_ia_insuffisant' => 'These formats need :credits AI credit(s), and you do not have enough this month. Nothing was started.',
        'aucun_format' => 'Select at least one format.',
        'langue_invalide' => 'This language is not available for rewriting.',
        'confirmation_requise' => 'Confirm the regeneration to start it.',
    ],

    'export' => [
        'conclusion' => 'Conclusion',
        'objet' => 'Subject',
        'apercu' => 'Preview text',
        'titre_defaut' => 'Rewrite',
    ],

];
