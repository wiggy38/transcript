<?php

return [

    'verification' => [
        'objet' => 'Vérifie ton adresse email',
        'titre' => 'Vérifie ton adresse',
        'texte' => 'Bonjour :nom, clique sur le bouton pour vérifier ton adresse. Tu pourras ensuite lancer tes transcriptions.',
        'bouton' => 'Vérifier mon adresse',
        'ignorer' => 'Si tu n\'as pas créé de compte Voxia, ignore ce message.',
    ],

    'reinitialisation' => [
        'objet' => 'Réinitialise ton mot de passe',
        'titre' => 'Nouveau mot de passe',
        'texte' => 'Bonjour :nom, tu as demandé à changer ton mot de passe. Le lien est valable un temps limité.',
        'bouton' => 'Choisir un nouveau mot de passe',
        'ignorer' => 'Si tu n\'es pas à l\'origine de cette demande, ignore ce message : ton mot de passe reste inchangé.',
    ],

    'suppression' => [
        'objet' => 'Ton compte Voxia a été supprimé',
        'titre' => 'Compte supprimé',
        'texte' => 'Bonjour :nom, ton compte Voxia et toutes tes données ont été supprimés : transcriptions, exports et fichiers MP3.',
        'ignorer' => 'Si tu n\'es pas à l\'origine de cette suppression, réponds à ce message dès que possible.',
    ],

    'transcription_prete' => [
        'objet' => 'Ta transcription est prête',
        'titre' => 'Transcription terminée',
        'texte' => 'Bonjour :nom, la vidéo « :titre » (:duree) est transcrite. Tu peux la lire et exporter le texte dès maintenant.',
        'bouton' => 'Voir la transcription',
        'ignorer' => 'Tu reçois ce message parce que tu as lancé une vidéo de plus de 15 minutes.',
    ],

    'signature' => 'L\'équipe Voxia',

];
