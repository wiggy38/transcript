<?php

return [

    'titre' => 'Réécrire',

    'sous_titre' => 'Transforme la vidéo en contenu prêt à publier. Chaque format coûte 1 crédit IA.',

    'formats_legende' => 'Formats à générer',

    'formats' => [
        'article' => 'Article de blog',
        'newsletter' => 'Newsletter',
        'linkedin' => 'Post LinkedIn',
        'thread' => 'Thread X',
    ],

    'ton' => 'Ton',

    'tons' => [
        'professionnel' => 'Professionnel',
        'conversationnel' => 'Conversationnel',
        'enthousiaste' => 'Enthousiaste',
    ],

    'longueur' => 'Longueur de l’article',

    'longueurs' => [
        'courte' => 'Courte',
        'moyenne' => 'Moyenne',
        'longue' => 'Longue',
    ],

    'aide_longueur' => 'S’applique à l’article seulement.',

    'langue' => 'Langue de sortie',

    'bouton' => 'Réécrire',

    'cout' => 'Coût : :credits crédit(s) IA (:unitaire par format). Rouvrir un format déjà généré est gratuit.',

    'plan_sans_acces' => 'La réécriture est incluse dans les plans Pro et Business.',

    'onglets' => 'Contenus générés',

    'statut' => [
        'en_attente' => 'Réécriture en file d’attente…',
        'en_cours' => 'Réécriture en cours…',
        'termine' => 'Réécriture terminée.',
        'echec' => 'La réécriture a échoué. Aucun crédit n’a été consommé.',
    ],

    'copier' => 'Copier',

    'copie' => 'Copié',

    'copier_bloc' => 'Copier : :bloc',

    'blocs' => [
        'titre' => 'le titre',
        'introduction' => 'l’introduction',
        'corps' => 'le corps',
        'post' => 'le post',
        'message' => 'le message',
    ],

    'compteur_linkedin' => ':caracteres caractères sur :max.',

    'exporter' => 'Exporter en',

    'verrou' => 'Disponible avec le plan :plan',

    'regenerer' => 'Régénérer ce format',

    'confirmer_regeneration' => 'Régénérer ce format coûte :credits crédit(s) IA. Continuer ?',

    'confirmer' => 'Confirmer',

    'annuler' => 'Annuler',

    'refus' => [
        'non_terminee' => 'La transcription doit être terminée pour être réécrite.',
        'plan_sans_acces' => 'La réécriture est incluse dans les plans Pro et Business.',
        'credit_ia_insuffisant' => 'Il te faut :credits crédit(s) IA pour ces formats, et tu n’en as pas assez ce mois-ci. Rien n’a été lancé.',
        'aucun_format' => 'Coche au moins un format.',
        'langue_invalide' => 'Cette langue n’est pas disponible pour la réécriture.',
        'confirmation_requise' => 'Confirme la régénération pour la lancer.',
    ],

    'export' => [
        'conclusion' => 'Conclusion',
        'objet' => 'Objet',
        'apercu' => 'Aperçu',
        'titre_defaut' => 'Réécriture',
    ],

];
