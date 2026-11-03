<?php

return [

    'utilisateur_requis' => 'Indique le compte avec --user=ton@email.',
    'utilisateur_introuvable' => 'Aucun compte ne correspond à cet e-mail.',
    'fichier_introuvable' => 'Ce fichier est introuvable ou illisible.',
    'lancee' => 'Transcription n° :id lancée. Suivi de la progression :',
    'statut' => 'Statut : :statut',
    'echec' => 'La transcription a échoué : :motif',
    'delai_depasse' => 'Le suivi a été arrêté avant la fin. La transcription continue en arrière-plan.',
    'paragraphes_total' => ':total paragraphes au total. Les 10 premiers :',

    'statuts' => [
        'en_attente' => 'En attente',
        'telechargement' => 'Téléchargement',
        'transcription' => 'Transcription',
        'termine' => 'Terminé',
        'echec' => 'Échec',
    ],

    'etapes' => [
        'metadonnees' => 'Métadonnées',
        'telechargement' => 'Téléchargement',
        'extraction_audio' => 'MP3',
        'transcription' => 'Transcription',
        'paragraphes' => 'Paragraphes',
        'enregistrement' => 'Enregistrement',
    ],

];
