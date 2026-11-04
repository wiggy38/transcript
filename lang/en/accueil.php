<?php

// Landing page (Signal mock-up, docs/maquette-signal.html). Prices and figures are not written
// here: they come from config/voxia.php and are passed as replacements (:name).

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
        'pastille' => 'Video → text, in a minute or two',
        'titre' => 'Every video has words in it.',
        'titre_accent' => 'Voxia writes them down.',
        'sous_titre' => 'Paste a YouTube or TikTok link, or drop your file. You get the timestamped transcript, the subtitles and the audio, filed in your library.',
        'champ' => 'Video link',
        'placeholder' => 'https://youtube.com/watch?v=…',
        'bouton' => 'Transcribe',
        'depot' => 'Or :lien. Accepted formats: :formats. Free to start.',
        'depot_lien' => 'drop a file',
    ],

    'apercu' => [
        'fenetre' => 'Vlog #34 — My pottery studio',
        'source' => 'YOUTUBE · 12:03 · EN · 1,842 WORDS',
        'panneau' => 'TRANSCRIPT',
        'onde' => 'Decorative audio waveform',
        'lignes' => [
            ['temps' => '00:00', 'texte' => "Welcome to the studio. Yes, there's clay everywhere."],
            ['temps' => '00:06', 'texte' => 'Today we throw six bowls, and we will ruin at least two.'],
            ['temps' => '00:13', 'texte' => 'The secret is centring the clay before you try to go fast.'],
            ['temps' => '00:19', 'texte' => "If it isn't centred, nothing will hold."],
        ],
        'boutons' => ['Copy', '.txt', '.srt', '.vtt', '.mp3'],
    ],

    'bandeau' => [
        'extraits' => [
            ['temps' => '00:00', 'texte' => 'Do you wake up with tight skin?'],
            ['temps' => '02:14', 'texte' => 'Three tools, one workflow'],
            ['temps' => '00:31', 'texte' => 'The link is right below'],
            ['temps' => '14:52', 'texte' => 'What I wish I had known before starting my channel'],
            ['temps' => '00:06', 'texte' => 'Today we throw six bowls'],
            ['temps' => '08:40', 'texte' => 'The question I get asked most often'],
        ],
    ],

    'usages' => [
        'titre' => 'Two jobs,',
        'titre_accent' => 'one same move.',
        'intro' => 'You make videos, or you study other people’s. Either way, you need the text.',
        'createurs' => [
            'numero' => '01 — CREATORS',
            'titre' => 'One transcription, four formats.',
            'texte' => 'You talked for twenty minutes. Now you only need to cut, not rewrite everything.',
            'formats' => [
                ['libelle' => 'Plain text', 'extension' => '.txt'],
                ['libelle' => 'Document', 'extension' => '.pdf'],
                ['libelle' => 'Subtitles', 'extension' => '.srt .vtt', 'accent' => true],
                ['libelle' => 'Podcast', 'extension' => '.mp3'],
            ],
        ],
        'marketeurs' => [
            'numero' => '02 — MARKETERS',
            'titre' => 'A video ad script, broken down.',
            'texte' => 'Spot the hook, the proof and the call to action without watching the video ten times.',
            'pub' => 'Serum ad — competitor A',
            'duree' => '0:42',
            'parties' => [
                ['libelle' => 'hook', 'secondes' => 3],
                ['libelle' => 'problem', 'secondes' => 6],
                ['libelle' => 'proof', 'secondes' => 12],
                ['libelle' => 'offer', 'secondes' => 13],
                ['libelle' => 'call to action', 'secondes' => 8],
            ],
            'description_parties' => 'A 42-second ad split into five parts: hook 3 s, problem 6 s, proof 12 s, offer 13 s, call to action 8 s.',
            'citation_repere' => 'HOOK · 00:00 → 00:03',
            'citation' => '“Do you wake up with tight skin?”',
        ],
    ],

    'fonctionnement' => [
        'titre' => 'From link to text.',
        'etapes' => [
            ['titre' => 'Paste the link', 'texte' => 'YouTube, TikTok, or your own file.'],
            ['titre' => 'We extract the audio', 'texte' => 'Clean, as MP3, ready to listen to.'],
            ['titre' => 'We transcribe', 'texte' => 'Punctuated, split into sentences, timestamped.'],
            ['titre' => 'You export', 'texte' => 'TXT, PDF, SRT, VTT or MP3, in one click.'],
        ],
    ],

    'fiche' => [
        'titre' => 'Tech specs',
        'entrees' => 'Inputs',
        'entrees_valeur' => 'YouTube · TikTok · :fichiers',
        'sorties' => 'Outputs',
        'sorties_valeur' => 'TXT · PDF · :sous_titres · MP3',
        'horodatage' => 'Timestamps',
        'horodatage_valeur' => 'Every sentence',
        'langues' => 'Languages',
        'langues_valeur' => 'Auto-detection · :codes',
    ],

    'tarifs' => [
        'titre' => 'Pricing',
        'intro' => 'Start for free. Move up to Pro when Voxia becomes a work tool.',
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
        'titre' => 'Questions',
        'liste' => [
            [
                'question' => 'Which languages are supported?',
                'reponse' => 'Voxia detects the spoken language. You can also force one of these: :langues.',
            ],
            [
                'question' => 'Can I transcribe any video?',
                'reponse' => 'Your own videos, your clients’ videos, or public videos you analyse. You remain responsible for how you use the text.',
            ],
            [
                'question' => 'How long are my files kept?',
                'reponse' => 'Free: transcripts kept :historique days; MP3s and uploaded files kept :media days [TO CONFIRM]. Pro and Pack: unlimited history. You can delete a transcript at any time.',
            ],
        ],
    ],

    'langues' => [
        'fr' => 'French',
        'en' => 'English',
        'es' => 'Spanish',
        'de' => 'German',
        'it' => 'Italian',
        'pt' => 'Portuguese',
    ],

    'pages' => [
        'confidentialite' => [
            'titre' => 'Privacy',
            'texte' => 'Text awaiting legal review. [TO CONFIRM]',
        ],
        'conditions' => [
            'titre' => 'Terms',
            'texte' => 'Terms of use awaiting legal review. [TO CONFIRM]',
        ],
        'contact' => [
            'titre' => 'Contact',
            'texte' => 'Contact address awaiting confirmation. [TO CONFIRM]',
        ],
    ],

];
