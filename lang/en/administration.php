<?php

return [

    'marque' => 'Voxia · Administration',

    'navigation' => [
        'groupe' => 'Operations',
        'tableau' => 'Dashboard',
        'utilisateurs' => 'Users',
        'traitements' => 'Jobs',
        'journal' => 'Audit log',
    ],

    'utilisateurs' => [
        'titre' => 'Users',
        'nom' => 'Name',
        'email' => 'Email',
        'plan' => 'Plan',
        'inscription' => 'Signed up',
        'consommation' => 'Usage this month',
        'filtre_plan' => 'Plan',
        'action' => 'Adjust a quota',
        'action_titre' => 'Adjust a quota',
        'compteur' => 'Counter',
        'quantite' => 'Amount',
        'quantite_aide' => 'Positive to add to the quota, negative to remove. Hours are entered in hours.',
        'motif' => 'Reason (required, logged)',
        'succes' => 'Quota adjusted. Action logged.',
    ],

    'compteurs' => [
        'videos' => 'Videos this month',
        'heures_mois' => 'Hours this month',
        'heures_pack' => 'Hours of the oldest Pack',
        'credits_ia' => 'AI credits this month',
    ],

    'traitements' => [
        'titre' => 'Jobs',
        'video' => 'Video',
        'utilisateur' => 'User',
        'plateforme' => 'Platform',
        'statut' => 'Status',
        'motif' => 'Failure reason',
        'duree_video' => 'Video length',
        'temps' => 'Processing time',
        'cout' => 'Estimated cost',
        'date' => 'Date',
        'periode' => 'Period',
        'du' => 'From',
        'au' => 'To',
        'etapes' => 'Steps',
        'rembourser' => 'Refund',
        'rembourser_titre' => 'Refund this job',
        'rembourser_description' => 'Every usage line of this job is reversed by an opposite line in the usage log.',
        'motif_remboursement' => 'Reason (required, logged)',
        'succes' => 'Job refunded: :nombre line(s) reversed.',
        'etape' => 'Step',
        'duree' => 'Duration',
        'resultat' => 'Result',
        'message' => 'Message',
        'cout_etape' => 'Cost',
        'ok' => 'Success',
        'echec' => 'Failed',
    ],

    'journal' => [
        'titre' => 'Audit log',
        'date' => 'Date',
        'administrateur' => 'Administrator',
        'utilisateur' => 'User',
        'action' => 'Action',
        'motif' => 'Reason',
        'details' => 'Details',
    ],

    'tableau' => [
        'titre' => 'Dashboard',
        'traitements_jour' => 'Jobs today',
        'echecs' => 'Download failures (:heures h)',
        'echecs_detail' => ':echecs failure(s) out of :tentatives attempt(s)',
        'echecs_aucun' => 'No attempt in this period',
        'cout_heure' => 'Average cost per video hour (:jours d)',
        'cout_heure_aucun' => 'No completed transcription',
        'inscriptions' => 'Sign-ups (:jours d)',
        'conversions' => 'Free to paid conversions (:jours d)',
        'conversions_detail' => 'First purchase (subscription or Pack) in this period',
    ],

    'consommation' => [
        'videos' => ':utilisees / :limite videos',
        'heures' => ':utilisees / :limite',
        'credits' => ':utilisees / :limite AI credits',
        'packs' => ':nombre active Pack, :restant left|:nombre active Packs, :restant left',
        'aucune' => 'No usage',
    ],

    'refus' => [
        'motif_vide' => 'The reason is required.',
        'quantite_nulle' => 'The amount cannot be zero.',
        'cible_indisponible' => 'This counter does not exist for this account.',
        'pas_en_echec' => 'Only a failed job can be refunded.',
        'rien_a_rembourser' => 'No usage to refund for this job.',
    ],

    'actions_journal' => [
        'ajustement_quota' => 'Quota adjustment',
        'remboursement' => 'Refund',
    ],

    'courriel' => [
        'alerte_echecs' => [
            'objet' => 'Voxia alert: download failures above threshold',
            'titre' => 'Download failures',
            'texte' => 'Hello :nom, over the last :fenetre minutes, :taux % of downloads failed (threshold: :seuil %).',
            'chiffres' => ':echecs failure(s) out of :tentatives attempt(s).',
            'bouton' => 'Open the back-office',
            'ignorer' => 'Until this delay has passed, no new alert will be sent.',
        ],
    ],

    'commandes' => [
        'admin_cree' => 'The account :email is now an administrator.',
        'admin_inconnu' => 'No account with the email :email.',
        'alerte_envoyee' => 'Alert sent to administrators.',
        'alerte_non_envoyee' => 'No alert to send.',
    ],

    'etapes' => [
        'metadonnees' => 'Metadata',
        'telechargement' => 'Download',
        'extraction_audio' => 'Audio extraction',
        'transcription' => 'Transcription',
        'paragraphes' => 'Paragraphs',
        'enregistrement' => 'Saving',
        'analyse_script' => 'Script analysis',
    ],

    'statuts' => [
        'en_attente' => 'Queued',
        'telechargement' => 'Downloading',
        'transcription' => 'Transcribing',
        'termine' => 'Done',
        'echec' => 'Failed',
    ],

    'plateformes' => [
        'youtube' => 'YouTube',
        'tiktok' => 'TikTok',
        'fichier' => 'File',
    ],

    'motifs' => [
        'lien_invalide' => 'Invalid link',
        'video_privee' => 'Private video',
        'video_supprimee' => 'Deleted video',
        'bloquee_region' => 'Blocked in region',
        'live_en_cours' => 'Live in progress',
        'trop_longue' => 'Too long',
        'quota_epuise' => 'Quota used up',
        'sans_audio' => 'No audio',
        'format_non_supporte' => 'Unsupported format',
        'erreur_technique' => 'Technical error',
    ],

];
