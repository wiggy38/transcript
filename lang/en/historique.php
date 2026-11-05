<?php

return [

    'titre' => 'History',
    'sous_titre' => 'All your transcriptions, from newest to oldest.',

    'recherche' => [
        'libelle' => 'Search my transcriptions',
        'aide' => 'A word or a phrase, across all your text.',
        'bouton' => 'Search',
        'effacer' => 'Clear search',
        'gratuit' => 'Searching your transcriptions is reserved to the Pro and Pack plans. Upgrade to Pro to find a passage in one word.',
        'resultats' => '{0} No result for “:terme”.|{1} One result for “:terme”.|[2,*] :nombre results for “:terme”.',
        'aller' => 'Open at this moment',
        'passer_pro' => 'Upgrade to Pro',
        'liste_aria' => 'Search results',
    ],

    'tri' => [
        'libelle' => 'Sort by date',
        'recent' => 'Newest first',
        'ancien' => 'Oldest first',
        'bouton' => 'Apply',
    ],

    'colonnes' => [
        'miniature' => 'Thumbnail',
        'titre' => 'Title',
        'plateforme' => 'Platform',
        'duree' => 'Duration',
        'date' => 'Date',
        'statut' => 'Status',
        'actions' => 'Actions',
    ],

    'plateformes' => [
        'youtube' => 'YouTube',
        'tiktok' => 'TikTok',
        'fichier' => 'Imported file',
    ],

    'suppression_prevue' => 'Deleted on :date',
    'supprimer' => 'Delete',
    'supprimer_aria' => 'Delete “:titre”',
    'ouvrir' => 'Open',
    'sans_titre' => 'Untitled',

    'vide' => 'No transcription yet. Start one from “New transcription”.',
    'vide_recherche' => 'No result to show.',

    'pagination' => [
        'precedente' => 'Previous',
        'suivante' => 'Next',
        'page' => 'Page :page of :total',
        'aria' => 'History pages',
    ],

    'statut' => [
        'supprimee' => 'Transcription deleted. It is gone from your history and from search.',
    ],

    'confirmation' => [
        'titre' => 'Delete this transcription?',
        'texte' => 'The transcription “:titre”, its text and its MP3 will be deleted permanently. This cannot be undone.',
        'bouton' => 'Delete permanently',
        'annuler' => 'Cancel',
        'retour' => 'Back to history',
    ],

    'commandes' => [
        'purge' => ':nombre expired transcription(s) deleted.',
        'rappel' => ':nombre transcription(s) reported by email.',
        'medias' => 'MP3 removed: :mp3. Imported files removed: :fichiers.',
    ],

];
