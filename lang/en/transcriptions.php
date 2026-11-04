<?php

return [

    'lien' => [
        'titre' => 'Video link',
        'champ' => 'YouTube or TikTok link',
        'aide' => 'A public video is enough.',
        'bouton' => 'Transcribe →',
        'lecture' => 'Reading the video details…',
        'apercu_pas_pret' => 'The preview is not ready yet. Wait a moment.',
    ],

    'apercu' => [
        'miniature' => 'Video thumbnail',
        'duree' => 'Duration',
        'chaine' => 'Channel',
        'lancer' => 'Start transcription',
        'deja' => 'You have already transcribed this video.',
        'voir' => 'View the transcription',
        'limite' => 'Your video is longer than the :minutes minutes of the Free plan. Only the first :minutes minutes will be transcribed. Upgrade to Pro for the full transcription.',
    ],

    'langue' => [
        'libelle' => 'Language',
        'auto' => 'Automatic detection',
        'invalide' => 'Choose a language from the list.',
    ],

    'langues' => [
        'fr' => 'French',
        'en' => 'English',
        'es' => 'Spanish',
        'de' => 'German',
        'it' => 'Italian',
        'pt' => 'Portuguese',
    ],

    'fichier' => [
        'titre' => 'Or a file',
        'glisser' => 'Drop your file here',
        'aide' => 'Formats: :formats. Maximum size: :max GB.',
        'parcourir' => 'Browse',
        'envoi' => 'File upload',
        'progression' => 'Upload: :pourcent%',
        'pret' => 'File ready: :nom',
        'lancer' => 'Start transcription of the file',
        'retirer' => 'Remove',
    ],

    'televersement' => [
        'format' => 'This file type is not accepted. Use MP3, M4A, WAV, MP4, MOV or WEBM.',
        'trop_gros' => 'This file is larger than the maximum of :max MB.',
        'vide' => 'This file is empty.',
        'echec' => 'The upload failed. Start it again from the beginning.',
        'decoupage' => 'The upload is incomplete. Start it again from the beginning.',
    ],

    'page' => [
        'titre' => 'Transcription',
        'retour' => 'Back to the new transcription',
        'nouvelle' => 'New transcription',
        'lancee_le' => 'Started on :date',
        'en_cours' => 'The transcription is in progress. You can close this page: it keeps going, and you will find it in your history.',
        'termine' => 'Transcription complete.',
        'actualisation' => 'The status updates by itself.',
        'apercu' => 'Preview: only the first :minutes minutes were transcribed. Upgrade to Pro for the full transcription.',
    ],

    'lecture' => [
        'plateforme' => 'Platform',
        'plateformes' => [
            'youtube' => 'YouTube',
            'tiktok' => 'TikTok',
            'fichier' => 'File',
        ],
        'duree' => 'Duration',
        'langue' => 'Language',
        'mots' => ':nombre words',
        'lecteur_youtube' => 'YouTube video player',
        'lecteur_audio' => 'Audio player for the transcription',
        'audio_navigateur' => 'Your browser cannot play this audio.',
        'audio_indisponible' => 'Audio is not available for this transcription.',
        'lecteur_erreur' => 'The player did not load. Check your connection, then reload the page.',
        'rechercher' => 'Search in the transcription',
        'precedent' => 'Previous',
        'suivant' => 'Next',
        'aucun_resultat' => 'No result',
        'resultat_sur' => ':position of :total',
        'horodatages' => 'Timestamps',
        'suivre' => 'Follow playback',
        'lire_a' => 'Play from :temps',
        'vide' => 'This transcription has no text.',
        'bandeau_pro' => 'Upgrade to Pro for the full transcription.',
        'passer_pro' => 'See the Pro plans and transcribe the full video',
        'copier' => 'Copy text',
        'copie_ok' => 'Text copied.',
        'copie_echec' => 'Copy failed. Select the text and copy it by hand.',
        'telecharger_mp3' => 'MP3',
        'mp3_supprime_bouton' => 'MP3 deleted',
        'mp3_supprime' => 'The MP3 was deleted after :jours days, under the retention policy. The text and exports remain available.',
        'avec_horodatages' => 'With timestamps',
        'exporter_verrouille' => 'Available from the :plan plan.',
    ],

];
