<?php

return [

    'bouton' => 'Analyser le script',

    'titre' => 'Structure du script',

    'sous_titre' => 'Accroche, problème, preuve, offre et appel à l’action, avec leurs horodatages.',

    'credit' => 'Une analyse réussie consomme 1 crédit IA. Un échec n’en consomme aucun.',

    'statut' => [
        'en_attente' => 'Analyse en file d’attente…',
        'analyse' => 'Analyse en cours…',
        'echec' => 'L’analyse a échoué. Aucun crédit n’a été consommé.',
        'termine' => 'Analyse terminée.',
    ],

    'refus' => [
        'duree_trop_longue' => 'L’analyse est réservée aux vidéos de :minutes minutes au plus.',
        'duree_inconnue' => 'La durée de cette transcription est inconnue : l’analyse n’est pas possible.',
        'non_terminee' => 'La transcription doit être terminée pour être analysée.',
        'credit_ia_insuffisant' => 'Tu n’as plus de crédit IA pour cette période. Ton plan Gratuit ou Pack n’inclut pas l’analyse de script.',
    ],

    'types' => [
        'accroche' => 'Accroche',
        'probleme' => 'Problème',
        'preuve' => 'Preuve',
        'offre' => 'Offre',
        'appel_action' => 'Appel à l’action',
    ],

    'non_detectee' => 'Non détectée',

    'aucune_partie' => 'Aucune partie n’a été détectée dans ce script.',

    'exporter' => 'Exporter l’analyse',

    'pastille' => 'Script analysé',

    'pdf' => [
        'titre' => 'Analyse du script',
    ],

];
