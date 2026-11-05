<?php

// Page d'accueil (direction « Nocturne », docs/design_handoff_voxia_nocturne). Les chiffres et prix
// ne sont pas écrits ici : ils viennent de config/voxia.php et sont passés en remplacements (:nom).

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
        'sources' => [
            'youtube' => 'YouTube',
            'tiktok' => 'TikTok',
            'fichiers' => 'Fichiers audio et vidéo',
        ],
        'titre' => 'Chaque vidéo contient un texte.',
        'titre_accent' => "Voxia te l'écrit.",
        'sous_titre' => 'Transcription horodatée, sous-titres SRT et VTT, fichier MP3. Tout est prêt en quelques minutes et rangé dans ta bibliothèque.',
        'champ' => 'Lien de la vidéo',
        'placeholder' => 'https://youtube.com/watch?v=…',
        'bouton' => 'Transcrire',
        'depot' => 'Ou :lien · :formats',
        'depot_lien' => 'dépose un fichier',
        'gratuit' => 'Gratuit pour commencer',
    ],

    'apercu' => [
        'fenetre' => 'Vlog #34 — Mon atelier de céramique',
        'statut' => 'Terminé',
        'temps' => '00:13 / 12:03',
        'onde' => 'Onde audio décorative',
        'lignes' => [
            ['temps' => '00:00', 'texte' => "Bienvenue dans l'atelier. Oui, il y a de l'argile partout."],
            ['temps' => '00:06', 'texte' => "Aujourd'hui on tourne six bols, et on en rate au moins deux."],
            ['temps' => '00:13', 'texte' => "Le secret, c'est de centrer la terre avant de vouloir aller vite."],
            ['temps' => '00:19', 'texte' => "Si elle n'est pas centrée, rien ne tiendra."],
        ],
        'formats' => ['TXT', 'PDF', 'SRT', 'VTT', 'MP3'],
    ],

    'fonctionnement' => [
        'titre' => 'Trois étapes,',
        'titre_accent' => 'aucun réglage.',
        'sous_titre' => 'Pas de compte à configurer ni de format à choisir avant de commencer.',
        'etapes' => [
            [
                'titre' => 'Colle un lien ou dépose un fichier',
                'texte' => 'Une vidéo YouTube ou TikTok publique, ou un fichier audio ou vidéo.',
            ],
            [
                'titre' => 'Voxia transcrit',
                'texte' => 'Le statut se met à jour tout seul. Tu peux fermer la page pendant le traitement.',
            ],
            [
                'titre' => 'Copie ou télécharge',
                'texte' => 'Texte horodaté, sous-titres et audio, rangés dans ta bibliothèque.',
            ],
        ],
        'illustrations' => [
            'lien' => 'youtube.com/watch?v=…',
            'ou' => 'ou',
            'fichier' => 'atelier-ceramique.mp4',
            'transcription' => 'Transcription',
            'auto' => 'mise à jour auto',
            'copier' => 'Copier le texte',
        ],
    ],

    'livrables' => [
        'titre' => 'Ce que tu récupères',
        'sous_titre' => 'Trois livrables pour chaque vidéo, à copier ou télécharger.',
        'cartes' => [
            [
                'formats' => ['TXT', 'PDF'],
                'titre' => 'Transcription horodatée',
                'texte' => 'Chaque phrase avec son horodatage. Clique dessus pour réécouter le passage.',
            ],
            [
                'formats' => ['SRT', 'VTT'],
                'titre' => 'Sous-titres',
                'texte' => 'Prêts à importer dans ton logiciel de montage ou sur ta plateforme.',
            ],
            [
                'formats' => ['MP3'],
                'titre' => 'Fichier audio',
                'texte' => 'La piste son de la vidéo, à réutiliser en podcast ou en extrait.',
            ],
        ],
    ],

    'usages' => [
        'titre' => 'Pour qui ?',
        'sous_titre' => 'Trois usages, un même point de départ : un lien ou un fichier.',
        'cartes' => [
            'createurs' => [
                'numero' => '01 · Sous-titres',
                'titre' => 'Créateurs de contenu',
                'texte' => 'Sous-titre tes vidéos, puis réutilise ce que tu as dit en description, en post ou en article.',
                'formats' => ['SRT', 'VTT', 'TXT'],
            ],
            'marketeurs' => [
                'numero' => '02 · Citations',
                'titre' => 'Marketeurs',
                'texte' => "Retrouve la phrase exacte d'un webinaire ou d'une interview, avec son horodatage, sans tout réécouter.",
                'formats' => ['TXT', 'PDF'],
            ],
            'ecommerce' => [
                'numero' => '03 · Fiches produit',
                'titre' => 'E-commerçants',
                'texte' => 'Transforme une démonstration produit en fiche, en FAQ ou en script de publicité.',
                'formats' => ['TXT', 'MP3'],
            ],
        ],
        'scene' => [
            'citation_copiee' => 'Citation copiée · 00:19',
            'fiche' => 'Fiche produit',
            'faq' => 'FAQ · 4 Q',
            'demo' => 'Démo · 03:42',
            'recherche' => 'centrée',
        ],
    ],

    'tarifs' => [
        'titre' => 'Tarifs',
        'intro' => 'Commence sans payer. Passe à Pro quand Voxia devient un outil de travail.',
        'provisoire' => 'Prix et quotas provisoires.',
        'recommande' => 'Recommandé',
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
        'titre' => 'Questions fréquentes',
        'liste' => [
            [
                'question' => 'Quels liens sont acceptés ?',
                'reponse' => 'Les vidéos YouTube et TikTok publiques.',
            ],
            [
                'question' => 'Quels fichiers puis-je déposer ?',
                'reponse' => 'MP3, M4A, WAV, MP4, MOV et WEBM, jusqu’à la taille maximale de ton plan.',
            ],
            [
                'question' => 'Dans quels formats puis-je exporter ?',
                'reponse' => 'TXT, SRT et MP3 sur tous les plans ; PDF et VTT à partir du plan Pro.',
            ],
            [
                'question' => 'Une vidéo en échec est-elle décomptée ?',
                'reponse' => 'Non. Seules les transcriptions terminées comptent dans ton quota.',
            ],
            [
                'question' => 'Quelles langues sont reconnues ?',
                'reponse' => 'Voxia détecte la langue parlée. Tu peux aussi la forcer parmi : :langues.',
            ],
            [
                'question' => 'Combien de temps mes fichiers sont-ils gardés ?',
                'reponse' => 'Gratuit : transcriptions gardées :historique jours ; MP3 et fichiers importés gardés :media jours [À CONFIRMER]. Pro et Pack : historique sans limite. Tu peux supprimer une transcription à tout moment.',
            ],
        ],
    ],

    'cta' => [
        'titre' => 'Ta prochaine vidéo, en texte.',
        'bouton' => 'Coller un lien',
    ],

    'langues' => [
        'fr' => 'français',
        'en' => 'anglais',
        'es' => 'espagnol',
        'de' => 'allemand',
        'it' => 'italien',
        'pt' => 'portugais',
    ],

];
