<?php

return [

    'titre' => 'Historique',
    'sous_titre' => 'Toutes tes transcriptions, de la plus récente à la plus ancienne.',

    'recherche' => [
        'libelle' => 'Chercher dans mes transcriptions',
        'aide' => 'Un mot ou une expression, dans tout ton texte.',
        'bouton' => 'Chercher',
        'effacer' => 'Effacer la recherche',
        'gratuit' => 'La recherche dans tes transcriptions est réservée au plan Pro et Pack. Passe au Pro pour retrouver un passage en un mot.',
        'resultats' => '{0} Aucun résultat pour « :terme ».|{1} Un résultat pour « :terme ».|[2,*] :nombre résultats pour « :terme ».',
        'aller' => 'Ouvrir à ce moment',
        'passer_pro' => 'Passer au Pro',
        'liste_aria' => 'Résultats de la recherche',
    ],

    'tri' => [
        'libelle' => 'Trier par date',
        'recent' => 'Plus récentes d’abord',
        'ancien' => 'Plus anciennes d’abord',
        'bouton' => 'Appliquer',
    ],

    'colonnes' => [
        'miniature' => 'Miniature',
        'titre' => 'Titre',
        'plateforme' => 'Plateforme',
        'duree' => 'Durée',
        'date' => 'Date',
        'statut' => 'Statut',
        'actions' => 'Actions',
    ],

    'plateformes' => [
        'youtube' => 'YouTube',
        'tiktok' => 'TikTok',
        'fichier' => 'Fichier importé',
    ],

    'suppression_prevue' => 'Suppression le :date',
    'supprimer' => 'Supprimer',
    'supprimer_aria' => 'Supprimer « :titre »',
    'ouvrir' => 'Ouvrir',
    'sans_titre' => 'Sans titre',

    'vide' => 'Aucune transcription pour l’instant. Lance-en une depuis « Nouvelle transcription ».',
    'vide_recherche' => 'Aucun résultat à afficher.',

    'pagination' => [
        'precedente' => 'Précédente',
        'suivante' => 'Suivante',
        'page' => 'Page :page sur :total',
        'aria' => 'Pages de l’historique',
    ],

    'statut' => [
        'supprimee' => 'Transcription supprimée. Elle a disparu de ton historique et de la recherche.',
    ],

    'confirmation' => [
        'titre' => 'Supprimer cette transcription ?',
        'texte' => 'La transcription « :titre », son texte et son MP3 seront supprimés définitivement. Cette action est irréversible.',
        'bouton' => 'Supprimer définitivement',
        'annuler' => 'Annuler',
        'retour' => 'Retour à l’historique',
    ],

    'commandes' => [
        'purge' => ':nombre transcription(s) expirée(s) supprimée(s).',
        'rappel' => ':nombre transcription(s) signalée(s) par email.',
        'medias' => 'MP3 retirés : :mp3. Fichiers importés retirés : :fichiers.',
    ],

];
