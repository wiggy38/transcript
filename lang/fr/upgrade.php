<?php

// Fenêtres d'upgrade (cahier §2, parcours d'upgrade). Les chiffres et les prix viennent de config/voxia.php.
return [
    'lignes' => [
        'videos' => ':nombre vidéos par mois',
        'duree_minutes' => 'Jusqu’à :minutes minutes par vidéo',
        'duree_heures' => 'Jusqu’à :heures h par vidéo',
        'exports' => 'Exports :formats',
        'heures_mois' => ':heures h de transcription par mois',
        'heures_pack' => ':heures h de transcription, valables :mois mois',
        'historique' => 'Historique illimité et recherche',
        'ia' => 'Crédits IA pour l’analyse de script',
        'lots' => 'Traitement par lots : jusqu’à :liens liens à la fois',
        'prioritaire' => 'File d’attente prioritaire',
    ],

    'periode' => [
        'mois' => '/ mois',
        'unique' => 'paiement unique',
    ],

    'annuel' => 'ou :prix par an, soit :mensuel par mois',

    'actions' => [
        'ton_plan' => 'Ton plan',
        'pro_mensuel' => 'Passer au Pro · :prix / mois',
        'pro_annuel' => 'Ou :prix / an',
        'pack' => 'Acheter le Pack · :prix',
        'business_mensuel' => 'Passer au Business · :prix / mois',
        'business_annuel' => 'Ou :prix / an',
        'gerer' => 'Gérer ma formule',
    ],

    'tarifs' => [
        'titre' => 'Tarifs',
        'sous_titre' => 'Commence sans payer. Passe au Pro quand Voxia devient un outil de travail.',
        'pied' => 'Prix en dollars US. La TVA et les factures sont gérées par Paddle, notre prestataire de paiement.',
    ],

    'quota' => [
        'titre' => 'Tu as utilisé tes vidéos du mois',
        'texte' => 'Le plan Gratuit inclut :limite vidéos par mois, et le compteur repart le 1er. Passe au Pro ou au Pack pour transcrire cette vidéo tout de suite.',
        'reprise' => 'Ton lien reste dans le champ : après paiement, tu reviens ici et tu peux le lancer.',
    ],

    'apercu' => [
        'titre' => 'Transcription complète',
        'texte' => 'Il reste :minutes minutes non transcrites sur cette vidéo de :duree. Passe au Pro : ta transcription sera relancée en version complète dès le paiement confirmé.',
    ],

    'export' => [
        'titre' => 'Export :format',
        'texte' => 'Le format :format est inclus à partir du plan :plan. Ta transcription reste ici.',
    ],

    'recherche' => [
        'titre' => 'Recherche dans l’historique',
        'texte' => 'Retrouve un passage dans toutes tes transcriptions, en un mot. La recherche est réservée au Pro et au Pack.',
    ],

    'analyse' => [
        'titre' => 'Analyse de script',
        'texte' => 'Ton plan n’inclut pas de crédits IA. Le Pro en inclut chaque mois pour analyser la structure de tes scripts.',
    ],

    'quota_business' => [
        'titre' => 'Tu as utilisé tes 30 h du mois',
        'texte' => 'Le plan Pro inclut :heures h de transcription par mois. Passe au Business pour :business h par mois et lance cette vidéo tout de suite.',
    ],

    'duree_business' => [
        'titre' => 'Cette vidéo dépasse 3 h',
        'texte' => 'Le plan Pro transcrit jusqu’à 3 h par vidéo. Le Business monte à 5 h par vidéo.',
    ],

    'reprise' => 'Ton lien est repris dans le champ. Lance la transcription quand tu veux.',
];
