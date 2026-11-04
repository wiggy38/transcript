<?php

return [

    'marque' => 'Voxia · Administration',

    'navigation' => [
        'groupe' => 'Exploitation',
        'tableau' => 'Tableau de bord',
        'utilisateurs' => 'Utilisateurs',
        'traitements' => 'Traitements',
        'journal' => 'Journal',
    ],

    'utilisateurs' => [
        'titre' => 'Utilisateurs',
        'nom' => 'Nom',
        'email' => 'Email',
        'plan' => 'Plan',
        'inscription' => 'Inscription',
        'consommation' => 'Consommation du mois',
        'filtre_plan' => 'Plan',
        'action' => 'Ajuster un quota',
        'action_titre' => 'Ajuster un quota',
        'compteur' => 'Compteur',
        'quantite' => 'Quantité',
        'quantite_aide' => 'Positif pour ajouter au quota, négatif pour retirer. Les heures se saisissent en heures.',
        'motif' => 'Motif (obligatoire, journalisé)',
        'succes' => 'Quota ajusté. Action inscrite au journal.',
    ],

    'compteurs' => [
        'videos' => 'Vidéos du mois',
        'heures_mois' => 'Heures du mois',
        'heures_pack' => 'Heures du plus ancien Pack',
        'credits_ia' => 'Crédits IA du mois',
    ],

    'traitements' => [
        'titre' => 'Traitements',
        'video' => 'Vidéo',
        'utilisateur' => 'Utilisateur',
        'plateforme' => 'Plateforme',
        'statut' => 'Statut',
        'motif' => 'Motif d’échec',
        'duree_video' => 'Durée vidéo',
        'temps' => 'Temps de traitement',
        'cout' => 'Coût estimé',
        'date' => 'Date',
        'periode' => 'Période',
        'du' => 'Du',
        'au' => 'Au',
        'etapes' => 'Étapes',
        'rembourser' => 'Rembourser',
        'rembourser_titre' => 'Rembourser ce traitement',
        'rembourser_description' => 'Chaque consommation de ce traitement est annulée par une ligne inverse dans le journal de consommation.',
        'motif_remboursement' => 'Motif (obligatoire, journalisé)',
        'succes' => 'Traitement remboursé : :nombre ligne(s) inversée(s).',
        'etape' => 'Étape',
        'duree' => 'Durée',
        'resultat' => 'Résultat',
        'message' => 'Message',
        'cout_etape' => 'Coût',
        'ok' => 'Réussi',
        'echec' => 'Échec',
    ],

    'journal' => [
        'titre' => 'Journal',
        'date' => 'Date',
        'administrateur' => 'Administrateur',
        'utilisateur' => 'Utilisateur',
        'action' => 'Action',
        'motif' => 'Motif',
        'details' => 'Détails',
    ],

    'tableau' => [
        'titre' => 'Tableau de bord',
        'traitements_jour' => 'Traitements du jour',
        'echecs' => 'Échecs de téléchargement (:heures h)',
        'echecs_detail' => ':echecs échec(s) sur :tentatives tentative(s)',
        'echecs_aucun' => 'Aucune tentative sur la période',
        'cout_heure' => 'Coût moyen par heure de vidéo (:jours j)',
        'cout_heure_aucun' => 'Aucune transcription terminée',
        'inscriptions' => 'Inscriptions (:jours j)',
        'conversions' => 'Conversions Gratuit → payant (:jours j)',
        'conversions_detail' => 'Premier achat (abonnement ou Pack) sur la période',
    ],

    'consommation' => [
        'videos' => ':utilisees / :limite vidéos',
        'heures' => ':utilisees / :limite',
        'credits' => ':utilisees / :limite crédits IA',
        'packs' => ':nombre Pack actif, :restant restantes|:nombre Packs actifs, :restant restantes',
        'aucune' => 'Aucune consommation',
    ],

    'refus' => [
        'motif_vide' => 'Le motif est obligatoire.',
        'quantite_nulle' => 'La quantité ne peut pas être nulle.',
        'cible_indisponible' => 'Ce compteur n’existe pas pour ce compte.',
        'pas_en_echec' => 'Seul un traitement raté peut être remboursé.',
        'rien_a_rembourser' => 'Aucune consommation à rembourser pour ce traitement.',
    ],

    'actions_journal' => [
        'ajustement_quota' => 'Ajustement de quota',
        'remboursement' => 'Remboursement',
    ],

    'courriel' => [
        'alerte_echecs' => [
            'objet' => 'Alerte Voxia : échecs de téléchargement au-dessus du seuil',
            'titre' => 'Échecs de téléchargement',
            'texte' => 'Bonjour :nom, sur les :fenetre dernières minutes, :taux % des téléchargements ont échoué (seuil : :seuil %).',
            'chiffres' => ':echecs échec(s) sur :tentatives tentative(s).',
            'bouton' => 'Ouvrir le back-office',
            'ignorer' => 'Tant que ce délai n’est pas écoulé, une nouvelle alerte ne partira pas.',
        ],
    ],

    'commandes' => [
        'admin_cree' => 'Le compte :email est administrateur.',
        'admin_inconnu' => 'Aucun compte avec l’adresse :email.',
        'alerte_envoyee' => 'Alerte envoyée aux administrateurs.',
        'alerte_non_envoyee' => 'Pas d’alerte à envoyer.',
    ],

    'etapes' => [
        'metadonnees' => 'Métadonnées',
        'telechargement' => 'Téléchargement',
        'extraction_audio' => 'Extraction audio',
        'transcription' => 'Transcription',
        'paragraphes' => 'Paragraphes',
        'enregistrement' => 'Enregistrement',
        'analyse_script' => 'Analyse de script',
    ],

    'statuts' => [
        'en_attente' => 'En attente',
        'telechargement' => 'Téléchargement',
        'transcription' => 'Transcription',
        'termine' => 'Terminé',
        'echec' => 'Échec',
    ],

    'plateformes' => [
        'youtube' => 'YouTube',
        'tiktok' => 'TikTok',
        'fichier' => 'Fichier',
    ],

    'motifs' => [
        'lien_invalide' => 'Lien invalide',
        'video_privee' => 'Vidéo privée',
        'video_supprimee' => 'Vidéo supprimée',
        'bloquee_region' => 'Bloquée dans la région',
        'live_en_cours' => 'Live en cours',
        'trop_longue' => 'Trop longue',
        'quota_epuise' => 'Quota épuisé',
        'sans_audio' => 'Sans audio',
        'format_non_supporte' => 'Format non pris en charge',
        'erreur_technique' => 'Erreur technique',
    ],

];
