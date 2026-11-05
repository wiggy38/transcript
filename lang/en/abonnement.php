<?php

return [

    'titre' => 'Subscription',
    'impayee' => 'Your last payment failed. Paddle will retry; update your payment method to keep Pro access.',

    'statut' => [
        'deja-pro' => 'You are already on the Pro plan.',
        'deja-abonne' => 'You already have a subscription. Change your plan from this page.',
        'retour-pro-programme' => 'You switch back to Pro at the end of your paid period. Your Business access stays open until then.',
        'paiement-indisponible' => 'Payment is not available right now. Please try again later.',
        'formule-changee' => 'Your plan is being changed. The new price will show shortly.',
        'resiliee' => 'Your subscription is cancelled. You keep Pro access until the end of the paid period.',
        'resiliation-annulee' => 'Cancellation undone. Your subscription continues.',
        'pas-d-abonnement' => 'You have no active Pro subscription.',
    ],

    'formule' => [
        'titre' => 'Your plan',
        'pro' => 'Pro',
        'mensuel' => 'monthly',
        'annuel' => 'yearly',
        'fin_acces' => 'Pro access until :date.',
        'prochaine' => 'Next billing on :date.',
        'passer_mensuel' => 'Switch to monthly',
        'passer_annuel' => 'Switch to yearly',
        'resilier' => 'Cancel subscription',
        'annuler_resiliation' => 'Undo cancellation',
        'free' => 'Free',
        'pack' => 'Pack',
        'aucun_pro' => 'No active Pro subscription. Cancelling keeps access until the end of the paid period.',
        'souscrire_mensuel' => 'Pro monthly, :montant $ per month',
        'souscrire_annuel' => 'Pro yearly, :montant $ per year',
        'acheter_pack' => 'Pack, :montant $ (one-time payment)',
        'business' => 'Business',
        'passer_business' => 'Upgrade to Business',
        'passer_pro' => 'Switch back to Pro (at the end of the period)',
        'souscrire_business_mensuel' => 'Business monthly, :montant $ per month',
        'souscrire_business_annuel' => 'Business yearly, :montant $ per year',
    ],

    'packs' => [
        'titre' => 'Packs purchased',
        'aucun' => 'No valid Pack.',
        'ligne' => ':heures h of transcription',
        'expire' => 'Expires on :date',
    ],

    'factures' => [
        'titre' => 'Invoices',
        'aide' => 'VAT and invoices are handled by Paddle, our merchant of record.',
        'aucune' => 'No invoice yet.',
        'sans_numero' => 'Invoice being prepared',
        'telecharger' => 'Download invoice',
        'statuts' => [
            'draft' => 'Draft',
            'ready' => 'Pending',
            'billed' => 'Billed',
            'paid' => 'Paid',
            'completed' => 'Paid',
            'past_due' => 'Unpaid',
            'canceled' => 'Cancelled',
        ],
    ],

    'paiement' => [
        'titre' => 'Payment',
        'titre_pro_mensuel' => 'Upgrade to Pro, monthly',
        'titre_pro_annuel' => 'Upgrade to Pro, yearly',
        'titre_pack' => 'Buy a Pack',
        'titre_business_mensuel' => 'Upgrade to Business, monthly',
        'titre_business_annuel' => 'Upgrade to Business, yearly',
        'montant_pro_mensuel' => ':montant $ per month. You can cancel at any time.',
        'montant_pro_annuel' => ':montant $ per year, about :mensuel $ per month.',
        'montant_pack' => ':montant $ for :heures h of transcription, valid :mois months. One-time payment, no subscription.',
        'montant_business_mensuel' => ':montant $ per month. You can cancel at any time.',
        'montant_business_annuel' => ':montant $ per year, about :mensuel $ per month.',
        'apercu' => 'Your transcription of “:titre” will be re-run in full once payment is confirmed.',
        'video_sans_titre' => 'this video',
        'taxes' => 'Prices in US dollars. VAT is calculated by Paddle according to your country.',
        'payer' => 'Pay',
        'retour' => 'Back to subscription',
        'indisponible' => 'Payment is not available right now.',
    ],

];
