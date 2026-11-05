<?php

return [

    'titre' => 'Abonnement',
    'impayee' => 'Ton dernier paiement a échoué. Paddle réessaie ; mets à jour ton moyen de paiement pour garder l\'accès Pro.',

    'statut' => [
        'deja-pro' => 'Tu es déjà au plan Pro.',
        'deja-abonne' => 'Tu as déjà un abonnement. Change de formule depuis cette page.',
        'retour-pro-programme' => 'Tu repasses au Pro à la fin de ta période payée. Ton accès Business reste ouvert jusque-là.',
        'paiement-indisponible' => 'Le paiement n\'est pas disponible pour le moment. Réessaie plus tard.',
        'formule-changee' => 'Ta formule est en cours de changement. Le nouveau prix apparaît dans quelques instants.',
        'resiliee' => 'Ton abonnement est résilié. Tu gardes l\'accès Pro jusqu\'à la fin de la période payée.',
        'resiliation-annulee' => 'La résiliation est annulée. Ton abonnement continue.',
        'pas-d-abonnement' => 'Tu n\'as pas d\'abonnement Pro en cours.',
    ],

    'formule' => [
        'titre' => 'Ta formule',
        'pro' => 'Pro',
        'mensuel' => 'mensuelle',
        'annuel' => 'annuelle',
        'fin_acces' => 'Accès Pro jusqu\'au :date.',
        'prochaine' => 'Prochaine facturation le :date.',
        'passer_mensuel' => 'Passer au mensuel',
        'passer_annuel' => 'Passer à l\'annuel',
        'resilier' => 'Résilier l\'abonnement',
        'annuler_resiliation' => 'Annuler la résiliation',
        'free' => 'Gratuit',
        'pack' => 'Pack',
        'aucun_pro' => 'Pas d\'abonnement Pro en cours. La résiliation laisse toujours l\'accès jusqu\'à la fin de la période payée.',
        'souscrire_mensuel' => 'Pro mensuel, :montant $ par mois',
        'souscrire_annuel' => 'Pro annuel, :montant $ par an',
        'acheter_pack' => 'Pack, :montant $ (paiement unique)',
        'business' => 'Business',
        'passer_business' => 'Passer au Business',
        'passer_pro' => 'Repasser au Pro (à la fin de la période)',
        'souscrire_business_mensuel' => 'Business mensuel, :montant $ par mois',
        'souscrire_business_annuel' => 'Business annuel, :montant $ par an',
    ],

    'packs' => [
        'titre' => 'Packs achetés',
        'aucun' => 'Aucun Pack valable.',
        'ligne' => ':heures h de transcription',
        'expire' => 'Expire le :date',
    ],

    'factures' => [
        'titre' => 'Factures',
        'aide' => 'La TVA et les factures sont gérées par Paddle, notre marchand officiel.',
        'aucune' => 'Aucune facture pour le moment.',
        'sans_numero' => 'Facture en préparation',
        'telecharger' => 'Télécharger la facture',
        'statuts' => [
            'draft' => 'Brouillon',
            'ready' => 'En attente',
            'billed' => 'Facturée',
            'paid' => 'Payée',
            'completed' => 'Payée',
            'past_due' => 'Impayée',
            'canceled' => 'Annulée',
        ],
    ],

    'paiement' => [
        'titre' => 'Paiement',
        'titre_pro_mensuel' => 'Passer au Pro, mensuel',
        'titre_pro_annuel' => 'Passer au Pro, annuel',
        'titre_pack' => 'Acheter un Pack',
        'titre_business_mensuel' => 'Passer au Business, mensuel',
        'titre_business_annuel' => 'Passer au Business, annuel',
        'montant_pro_mensuel' => ':montant $ par mois. Tu peux résilier à tout moment.',
        'montant_pro_annuel' => ':montant $ par an, soit environ :mensuel $ par mois.',
        'montant_pack' => ':montant $ pour :heures h de transcription, valables :mois mois. Paiement unique, sans abonnement.',
        'montant_business_mensuel' => ':montant $ par mois. Tu peux résilier à tout moment.',
        'montant_business_annuel' => ':montant $ par an, soit environ :mensuel $ par mois.',
        'apercu' => 'Ta transcription de « :titre » sera relancée en version complète dès le paiement confirmé.',
        'video_sans_titre' => 'cette vidéo',
        'taxes' => 'Prix en dollars US. La TVA est calculée par Paddle selon ton pays.',
        'payer' => 'Payer',
        'retour' => 'Retour à l\'abonnement',
        'indisponible' => 'Le paiement n\'est pas disponible pour le moment.',
    ],

];
