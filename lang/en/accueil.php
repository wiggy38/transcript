<?php

// Landing page (« Nocturne » direction, docs/design_handoff_voxia_nocturne). Prices and figures are not
// written here: they come from config/voxia.php and are passed as replacements (:name).

return [

    'titre_page' => 'Voxia — Every video contains a text',
    'description' => 'Paste a YouTube or TikTok link, or drop your file: timestamped transcript, SRT and VTT subtitles, MP3 audio.',

    'partage' => [
        'alt' => 'Voxia sharing card: an audio waveform, a timestamped transcript and the page title.',
    ],

    // Amounts: ":montant" is replaced by the formatted number; the symbol goes before the amount.
    'montant' => '$:montant',
    'separateur_decimal' => '.',

    'hero' => [
        'sources' => [
            'youtube' => 'YouTube',
            'tiktok' => 'TikTok',
            'fichiers' => 'Audio and video files',
        ],
        'titre' => 'Every video contains text.',
        'titre_accent' => 'Voxia writes it down.',
        'sous_titre' => 'Timestamped transcript, SRT and VTT subtitles, MP3 audio. Everything is ready in a few minutes and filed in your library.',
        'champ' => 'Video link',
        'placeholder' => 'https://youtube.com/watch?v=…',
        'bouton' => 'Transcribe',
        'depot' => 'Or :lien · :formats',
        'depot_lien' => 'drop a file',
        'gratuit' => 'Free to start',
    ],

    'apercu' => [
        'fenetre' => 'Vlog #34 — My pottery studio',
        'statut' => 'Done',
        'temps' => '00:13 / 12:03',
        'onde' => 'Decorative audio waveform',
        'lignes' => [
            ['temps' => '00:00', 'texte' => "Welcome to the studio. Yes, there's clay everywhere."],
            ['temps' => '00:06', 'texte' => 'Today we throw six bowls, and we will ruin at least two.'],
            ['temps' => '00:13', 'texte' => 'The secret is centring the clay before you try to go fast.'],
            ['temps' => '00:19', 'texte' => "If it isn't centred, nothing will hold."],
        ],
        'formats' => ['TXT', 'PDF', 'SRT', 'VTT', 'MP3'],
    ],

    'fonctionnement' => [
        'titre' => 'Three steps,',
        'titre_accent' => 'no setup.',
        'sous_titre' => 'No account to configure and no format to choose before you start.',
        'etapes' => [
            [
                'titre' => 'Paste a link or drop a file',
                'texte' => 'A public YouTube or TikTok video, or an audio or video file.',
            ],
            [
                'titre' => 'Voxia transcribes',
                'texte' => 'The status updates on its own. You can close the page while it processes.',
            ],
            [
                'titre' => 'Copy or download',
                'texte' => 'Timestamped text, subtitles and audio, filed in your library.',
            ],
        ],
        'illustrations' => [
            'lien' => 'youtube.com/watch?v=…',
            'ou' => 'or',
            'fichier' => 'pottery-studio.mp4',
            'transcription' => 'Transcription',
            'auto' => 'updates automatically',
            'copier' => 'Copy text',
        ],
    ],

    'livrables' => [
        'titre' => 'What you get',
        'sous_titre' => 'Three deliverables for every video, to copy or download.',
        'cartes' => [
            [
                'formats' => ['TXT', 'PDF'],
                'titre' => 'Timestamped transcript',
                'texte' => 'Every sentence with its timestamp. Click one to replay that passage.',
            ],
            [
                'formats' => ['SRT', 'VTT'],
                'titre' => 'Subtitles',
                'texte' => 'Ready to import into your editing software or your platform.',
            ],
            [
                'formats' => ['MP3'],
                'titre' => 'Audio file',
                'texte' => 'The video’s soundtrack, to reuse as a podcast or a clip.',
            ],
        ],
    ],

    'usages' => [
        'titre' => 'Who is it for?',
        'sous_titre' => 'Three uses, one starting point: a link or a file.',
        'cartes' => [
            'createurs' => [
                'numero' => '01 · Subtitles',
                'titre' => 'Content creators',
                'texte' => 'Subtitle your videos, then reuse what you said in a description, a post or an article.',
                'formats' => ['SRT', 'VTT', 'TXT'],
            ],
            'marketeurs' => [
                'numero' => '02 · Quotes',
                'titre' => 'Marketers',
                'texte' => 'Find the exact sentence from a webinar or an interview, with its timestamp, without replaying it all.',
                'formats' => ['TXT', 'PDF'],
            ],
            'ecommerce' => [
                'numero' => '03 · Product sheets',
                'titre' => 'E-commerce sellers',
                'texte' => 'Turn a product demo into a sheet, an FAQ or an ad script.',
                'formats' => ['TXT', 'MP3'],
            ],
        ],
        'scene' => [
            'citation_copiee' => 'Quote copied · 00:19',
            'fiche' => 'Product sheet',
            'faq' => 'FAQ · 4 Q',
            'demo' => 'Demo · 03:42',
            'recherche' => 'centred',
        ],
    ],

    'tarifs' => [
        'titre' => 'Pricing',
        'intro' => 'Start for free. Move to Pro when Voxia becomes a working tool.',
        'provisoire' => 'Prices and quotas are provisional.',
        'recommande' => 'Recommended',
        'gratuit' => [
            'lignes' => [
                ':nombre videos per month',
                'Up to :minutes minutes per video',
                'TXT and SRT exports',
                'MP3 included',
            ],
            'cta' => 'Start',
        ],
        'pro' => [
            'periode' => 'Billing',
            'mois' => 'Monthly',
            'an' => 'Yearly',
            'suffixe_mois' => '/ month',
            'suffixe_an' => '/ year',
            'lignes' => [
                ':heures h per month',
                'Videos up to :heures h',
                'All exports, including PDF and VTT',
                'Library and search',
                'Script analysis',
                'MP3 included',
            ],
            'cta' => 'Go Pro',
        ],
        'pack' => [
            'suffixe' => 'one-time payment',
            'lignes' => [
                ':heures h, valid for :mois months',
                'No subscription',
                'All exports, including PDF and VTT',
                'MP3 included',
            ],
            'cta' => 'Buy a Pack',
        ],
    ],

    'questions' => [
        'titre' => 'Frequently asked questions',
        'liste' => [
            [
                'question' => 'Which links are accepted?',
                'reponse' => 'Public YouTube and TikTok videos.',
            ],
            [
                'question' => 'Which files can I upload?',
                'reponse' => 'MP3, M4A, WAV, MP4, MOV and WEBM, up to the maximum size of your plan.',
            ],
            [
                'question' => 'Which formats can I export?',
                'reponse' => 'TXT, SRT and MP3 on every plan; PDF and VTT from the Pro plan.',
            ],
            [
                'question' => 'Is a failed video counted?',
                'reponse' => 'No. Only finished transcripts count towards your quota.',
            ],
            [
                'question' => 'Which languages are recognised?',
                'reponse' => 'Voxia detects the spoken language. You can also force it among: :langues.',
            ],
            [
                'question' => 'How long are my files kept?',
                'reponse' => 'Free: transcripts kept :historique days; MP3 and uploaded files kept :media days [TO CONFIRM]. Pro and Pack: history without limit. You can delete a transcript at any time.',
            ],
        ],
    ],

    'cta' => [
        'titre' => 'Your next video, in text.',
        'bouton' => 'Paste a link',
    ],

    'langues' => [
        'fr' => 'French',
        'en' => 'English',
        'es' => 'Spanish',
        'de' => 'German',
        'it' => 'Italian',
        'pt' => 'Portuguese',
    ],

];
