<?php

return [

    'verification' => [
        'objet' => 'Verify your email address',
        'titre' => 'Verify your address',
        'texte' => 'Hello :nom, click the button to verify your address. You can then start your transcriptions.',
        'bouton' => 'Verify my address',
        'ignorer' => 'If you did not create a Voxia account, you can ignore this message.',
    ],

    'reinitialisation' => [
        'objet' => 'Reset your password',
        'titre' => 'New password',
        'texte' => 'Hello :nom, you asked to change your password. The link is valid for a limited time.',
        'bouton' => 'Choose a new password',
        'ignorer' => 'If you did not ask for this, ignore this message: your password stays the same.',
    ],

    'suppression' => [
        'objet' => 'Your Voxia account has been deleted',
        'titre' => 'Account deleted',
        'texte' => 'Hello :nom, your Voxia account and all your data have been deleted: transcripts, exports and MP3 files.',
        'ignorer' => 'If you did not request this deletion, reply to this message as soon as possible.',
    ],

    'transcription_prete' => [
        'objet' => 'Your transcription is ready',
        'titre' => 'Transcription complete',
        'texte' => 'Hello :nom, the video “:titre” (:duree) is transcribed. You can read it and export the text now.',
        'bouton' => 'View the transcription',
        'ignorer' => 'You are receiving this message because you started a video longer than 15 minutes.',
    ],

    'signature' => 'The Voxia team',

];
