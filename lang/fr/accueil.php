<?php

// Page d'accueil (maquette Signal, docs/maquette-signal.html). Les chiffres et prix ne sont pas
// écrits ici : ils viennent de config/voxia.php et sont passés en remplacements (:nom).

return [

    'titre_page' => 'Voxia — Chaque vidéo contient un texte',
    'description' => 'Colle un lien YouTube ou TikTok, ou dépose ton fichier : transcription horodatée, sous-titres SRT et VTT, audio MP3.',

    'partage' => [
        'alt' => 'Carte de partage Voxia : une onde audio, une transcription horodatée et le titre de la page.',
    ],

    // Montants : ":montant" est remplacé par le nombre formaté, le symbole suit l'usage de la langue.
    'montant' => ":montant\u{00A0}$",
    'separateur_decimal' => ',',

    'hero' => [
        'pastille' => 'Vidéo → texte, en une ou deux minutes',
        'titre' => 'Chaque vidéo contient un texte.',
        'titre_accent' => "Voxia te l'écrit.",
        'sous_titre' => "Colle un lien YouTube ou TikTok, ou dépose ton fichier. Tu récupères la transcription horodatée, les sous-titres et l'audio, rangés dans ta bibliothèque.",
        'champ' => 'Lien de la vidéo',
        'placeholder' => 'https://youtube.com/watch?v=…',
        'bouton' => 'Transcrire',
        'depot' => 'Ou :lien. Formats acceptés : :formats. Gratuit pour commencer.',
        'depot_lien' => 'dépose un fichier',
    ],

    'apercu' => [
        'fenetre' => 'Vlog #34 — Mon atelier de céramique',
        'source' => 'YOUTUBE · 12:03 · FR · 1 842 MOTS',
        'panneau' => 'TRANSCRIPTION',
        'onde' => 'Onde audio décorative',
        'lignes' => [
            ['temps' => '00:00', 'texte' => "Bienvenue dans l'atelier. Oui, il y a de l'argile partout."],
            ['temps' => '00:06', 'texte' => "Aujourd'hui on tourne six bols, et on en rate au moins deux."],
            ['temps' => '00:13', 'texte' => "Le secret, c'est de centrer la terre avant de vouloir aller vite."],
            ['temps' => '00:19', 'texte' => "Si elle n'est pas centrée, rien ne tiendra."],
        ],
        'boutons' => ['Copier', '.txt', '.srt', '.vtt', '.mp3'],
    ],

    'bandeau' => [
        'extraits' => [
            ['temps' => '00:00', 'texte' => 'Tu te réveilles avec la peau qui tire ?'],
            ['temps' => '02:14', 'texte' => 'Trois outils, un seul workflow'],
            ['temps' => '00:31', 'texte' => 'Le lien est juste en dessous'],
            ['temps' => '14:52', 'texte' => "Ce que j'aurais aimé savoir avant de lancer ma chaîne"],
            ['temps' => '00:06', 'texte' => "Aujourd'hui on tourne six bols"],
            ['temps' => '08:40', 'texte' => "La question qu'on me pose le plus souvent"],
        ],
    ],

    'usages' => [
        'titre' => 'Deux métiers,',
        'titre_accent' => 'un même geste.',
        'intro' => 'Tu fabriques des vidéos, ou tu étudies celles des autres. Dans les deux cas, tu as besoin du texte.',
        'createurs' => [
            'numero' => '01 — CRÉATEURS',
            'titre' => 'Une transcription, quatre formats.',
            'texte' => 'Tu as parlé vingt minutes. Il te reste à couper, pas à tout réécrire.',
            'formats' => [
                ['libelle' => 'Texte', 'extension' => '.txt'],
                ['libelle' => 'Document', 'extension' => '.pdf'],
                ['libelle' => 'Sous-titres', 'extension' => '.srt .vtt', 'accent' => true],
                ['libelle' => 'Podcast', 'extension' => '.mp3'],
            ],
        ],
        'marketeurs' => [
            'numero' => '02 — MARKETEURS',
            'titre' => "Le script d'une pub, découpé.",
            'texte' => "Repère l'accroche, la preuve et l'appel à l'action sans regarder la vidéo dix fois.",
            'pub' => 'Pub sérum — concurrent A',
            'duree' => '0:42',
            'parties' => [
                ['libelle' => 'accroche', 'secondes' => 3],
                ['libelle' => 'problème', 'secondes' => 6],
                ['libelle' => 'preuve', 'secondes' => 12],
                ['libelle' => 'offre', 'secondes' => 13],
                ['libelle' => "appel à l'action", 'secondes' => 8],
            ],
            'description_parties' => 'Pub de 42 secondes découpée en cinq parties : accroche 3 s, problème 6 s, preuve 12 s, offre 13 s, appel à l\'action 8 s.',
            'citation_repere' => 'ACCROCHE · 00:00 → 00:03',
            'citation' => '« Tu te réveilles avec la peau qui tire ? »',
        ],
    ],

    'fonctionnement' => [
        'titre' => 'Du lien au texte.',
        'etapes' => [
            ['titre' => 'Colle le lien', 'texte' => 'YouTube, TikTok, ou ton propre fichier.'],
            ['titre' => "On extrait l'audio", 'texte' => 'Propre, en MP3, prêt à écouter.'],
            ['titre' => 'On transcrit', 'texte' => 'Ponctué, découpé en phrases, horodaté.'],
            ['titre' => 'Tu exportes', 'texte' => 'TXT, PDF, SRT, VTT ou MP3, en un clic.'],
        ],
    ],

    'fiche' => [
        'titre' => 'Fiche technique',
        'entrees' => 'Entrées',
        'entrees_valeur' => 'YouTube · TikTok · :fichiers',
        'sorties' => 'Sorties',
        'sorties_valeur' => 'TXT · PDF · :sous_titres · MP3',
        'horodatage' => 'Horodatage',
        'horodatage_valeur' => 'À chaque phrase',
        'langues' => 'Langues',
        'langues_valeur' => 'Détection auto · :codes',
    ],

    'tarifs' => [
        'titre' => 'Tarifs',
        'intro' => 'Commence sans payer. Passe à Pro quand Voxia devient un outil de travail.',
        'gratuit' => [
            'lignes' => [
                ':nombre vidéos par mois',
                "Jusqu'à :minutes minutes par vidéo",
                'Exports TXT et SRT',
                'MP3 inclus',
            ],
            'cta' => 'Commencer',
        ],
        'pro' => [
            'periode' => 'Facturation',
            'mois' => 'Mensuel',
            'an' => 'Annuel',
            'suffixe_mois' => '/ mois',
            'suffixe_an' => '/ an',
            'lignes' => [
                ':heures h par mois',
                "Vidéos jusqu'à :heures h",
                'Tous les exports, dont PDF et VTT',
                'Bibliothèque et recherche',
                'Analyse de scripts',
                'MP3 inclus',
            ],
            'cta' => 'Passer à Pro',
        ],
        'pack' => [
            'suffixe' => 'paiement unique',
            'lignes' => [
                ':heures h, valables :mois mois',
                'Sans abonnement',
                'Tous les exports, dont PDF et VTT',
                'MP3 inclus',
            ],
            'cta' => 'Acheter un Pack',
        ],
    ],

    'questions' => [
        'titre' => 'Questions',
        'liste' => [
            [
                'question' => 'Quelles langues sont reconnues ?',
                'reponse' => 'Voxia détecte la langue parlée. Tu peux aussi la forcer : :langues.',
            ],
            [
                'question' => "Je peux transcrire n'importe quelle vidéo ?",
                'reponse' => "Tes vidéos, celles de tes clients, ou des vidéos publiques que tu analyses. Tu restes responsable de l'usage que tu fais du texte.",
            ],
            [
                'question' => 'Combien de temps mes fichiers sont-ils gardés ?',
                'reponse' => 'Gratuit : transcriptions gardées :historique jours ; MP3 et fichiers importés gardés :media jours [À CONFIRMER]. Pro et Pack : historique sans limite. Tu peux supprimer une transcription à tout moment.',
            ],
        ],
    ],

    'langues' => [
        'fr' => 'français',
        'en' => 'anglais',
        'es' => 'espagnol',
        'de' => 'allemand',
        'it' => 'italien',
        'pt' => 'portugais',
    ],

    'pages' => [
        'confidentialite' => [
            'titre' => 'Confidentialité',
            'texte' => 'Texte en attente de validation juridique. [À CONFIRMER]',
        ],
        'conditions' => [
            'titre' => 'Conditions',
            'texte' => 'Conditions générales en attente de validation juridique. [À CONFIRMER]',
        ],
        'contact' => [
            'titre' => 'Contact',
            'texte' => 'Adresse de contact en attente de confirmation. [À CONFIRMER]',
        ],
    ],

];
