<?php

// Bibliothèque classée (V2) : dossiers, étiquettes, classement et filtres. Tutoiement.
return [

    'types' => [
        'niche' => 'Niche',
        'produit' => 'Produit',
        'concurrent' => 'Concurrent',
        'libre' => 'Libre',
    ],

    'navigation' => [
        'titre' => 'Bibliothèque',
        'toutes' => 'Toutes',
        'non_classees' => 'Non classé',
        'dossiers' => 'Dossiers',
        'chaines' => 'Chaînes et comptes',
        'etiquettes' => 'Étiquettes',
        'gerer' => 'Gérer dossiers et étiquettes',
        'aucun_dossier' => 'Aucun dossier pour l’instant.',
        'aucune_chaine' => 'Aucune chaîne pour l’instant.',
        'aucune_etiquette' => 'Aucune étiquette de ce type.',
        'aria' => 'Dossiers, chaînes et étiquettes',
    ],

    'filtres' => [
        'produit' => 'Produit',
        'produit_tous' => 'Tous les produits',
        'legende' => 'Filtrer la liste',
        'dossier_aucun' => 'Non classé',
        'etiquettes' => 'Étiquettes (toutes celles choisies)',
        'etiquettes_aide' => 'Maintiens Ctrl ou Cmd pour en choisir plusieurs.',
        'plateforme' => 'Plateforme',
        'plateforme_toutes' => 'Toutes',
        'portee' => 'Recherche dans le texte',
        'portee_tout' => 'Toute la bibliothèque',
        'portee_filtre' => 'Seulement la liste filtrée',
        'appliquer' => 'Appliquer les filtres',
        'reinitialiser' => 'Réinitialiser les filtres',
    ],

    'selection' => [
        'legende' => 'Sélectionner cette transcription',
        'total' => 'Sélection : :nombre transcription(s)',
        'dossier' => 'Dossier de destination',
        'choisir_dossier' => 'Choisir un dossier',
        'etiquette' => 'Étiquette à ajouter',
        'choisir_etiquette' => 'Choisir une étiquette',
        'deplacer' => 'Déplacer',
        'etiqueter' => 'Étiqueter',
        'supprimer' => 'Supprimer',
        'aide' => 'Coche des transcriptions, puis choisis une action. Supprimer est définitif.',
        'aucune' => 'Coche au moins une transcription.',
        'aucune_etiquette' => 'Choisis une étiquette à ajouter.',
    ],

    'classer' => [
        'bouton' => 'Classer',
        'titre' => 'Classer « :titre »',
        'dossier' => 'Dossier',
        'dossier_aide' => 'Une transcription est dans un dossier au plus.',
        'etiquettes' => 'Étiquettes',
        'etiquettes_aide' => 'Une transcription peut avoir plusieurs étiquettes.',
        'nouvelle' => 'Nouvelle étiquette',
        'nouvelle_aide' => 'Tape un nom. Si l’étiquette existe déjà dans ce type, elle est réutilisée.',
        'nouvelle_type' => 'Type de la nouvelle étiquette',
        'liste_suggestions' => 'Étiquettes déjà créées',
        'enregistrer' => 'Enregistrer le classement',
        'retour' => 'Retour à la bibliothèque',
        'aucune_etiquette' => 'Aucune étiquette pour l’instant. Crée-en une ci-dessous.',
    ],

    'gerer' => [
        'titre' => 'Dossiers et étiquettes',
        'sous_titre' => 'Range tes transcriptions dans des dossiers et étiquette-les par niche, produit, concurrent ou libre.',
        'lecture_seule' => 'Ton plan ne permet plus de modifier le classement. Tes dossiers et étiquettes restent visibles et filtrables. Passe au Pro ou au Business pour les modifier.',
        'dossiers' => 'Dossiers',
        'dossiers_aide' => 'Un seul niveau. Supprimer un dossier ne supprime aucune transcription : elles reviennent dans « Non classé ».',
        'nouveau_dossier' => 'Nouveau dossier',
        'creer' => 'Créer',
        'nom' => 'Nom',
        'renommer' => 'Renommer',
        'supprimer' => 'Supprimer',
        'supprimer_aria' => 'Supprimer le dossier « :nom »',
        'supprimer_etiquette_aria' => 'Supprimer l’étiquette « :nom »',
        'vide' => 'Aucun dossier pour l’instant.',
        'nombre' => ':nombre transcription(s)',
        'etiquettes' => 'Étiquettes',
        'etiquettes_aide' => 'Une étiquette porte un type. Supprimer une étiquette la retire des transcriptions, sans les supprimer.',
        'nouvelle_etiquette' => 'Nouvelle étiquette',
        'type' => 'Type',
        'vide_etiquettes' => 'Aucune étiquette pour l’instant.',
        'retour' => 'Retour à la bibliothèque',
    ],

    'statuts' => [
        'dossier-cree' => 'Dossier créé.',
        'dossier-renomme' => 'Dossier renommé.',
        'dossier-supprime' => 'Dossier supprimé. Ses transcriptions sont dans « Non classé ».',
        'etiquette-creee' => 'Étiquette créée.',
        'etiquette-renommee' => 'Étiquette renommée.',
        'etiquette-supprimee' => 'Étiquette supprimée.',
        'classement-enregistre' => 'Classement enregistré.',
        'selection-deplacee' => 'Transcriptions déplacées.',
        'selection-etiquetee' => 'Étiquettes ajoutées.',
        'selection-supprimee' => 'Transcriptions supprimées.',
    ],

    'erreurs' => [
        'dossier_existe' => 'Tu as déjà un dossier avec ce nom.',
        'etiquette_existe' => 'Tu as déjà une étiquette avec ce nom dans ce type.',
    ],

    'non_classe' => 'Non classé',
    'sans_titre' => 'Sans titre',

    'upgrade' => [
        'titre' => 'Classer tes transcriptions',
        'texte' => 'Range tes transcriptions dans des dossiers, étiquette-les par niche, produit ou concurrent, et filtre ta bibliothèque. Le classement est réservé au Pro et au Business.',
    ],

];
