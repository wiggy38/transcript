<?php

return [

    'utilisateur_requis' => 'Give the account with --user=you@email.',
    'utilisateur_introuvable' => 'No account matches this e-mail.',
    'fichier_introuvable' => 'This file is missing or unreadable.',
    'lancee' => 'Transcription #:id started. Following progress:',
    'statut' => 'Status: :statut',
    'echec' => 'The transcription failed: :motif',
    'delai_depasse' => 'Monitoring stopped before the end. The transcription keeps running in the background.',
    'paragraphes_total' => ':total paragraphs in total. The first 10:',

    'statuts' => [
        'en_attente' => 'Waiting',
        'telechargement' => 'Downloading',
        'transcription' => 'Transcribing',
        'termine' => 'Done',
        'echec' => 'Failed',
    ],

    'etapes' => [
        'metadonnees' => 'Metadata',
        'telechargement' => 'Download',
        'extraction_audio' => 'MP3',
        'transcription' => 'Transcription',
        'paragraphes' => 'Paragraphs',
        'enregistrement' => 'Saving',
    ],

];
