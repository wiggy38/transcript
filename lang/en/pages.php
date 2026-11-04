<?php

// Public pages: pricing, FAQ, legal notices, privacy, contact.
// Numbers (quotas, durations, prices, retention) are not written here: they come from
// config/voxia.php and are passed as replacements (:name). Legal texts are DRAFTS to be
// reviewed by a lawyer before launch (CLAUDE.md §7).

return [

    'brouillon' => 'DRAFT — to be reviewed by a lawyer before launch',
    'maj' => 'Draft dated :date. Values in square brackets still need to be completed.',
    'a_completer' => '[TO BE COMPLETED]',
    'a_confirmer' => '[TO BE CONFIRMED]',
    'forme' => 'legal form :statut',
    'email_manquant' => '[CONTACT EMAIL TO BE COMPLETED]',

    'tarifs' => [
        'titre' => 'Pricing',
        'intro' => 'Three plans at launch. Prices are in US dollars, and volumes are stated in plain hours.',
        'oui' => 'Yes',
        'non' => 'No',
        'colonnes' => [
            'fonctionnalite' => 'What is included',
        ],
        'lignes' => [
            'prix' => 'Price',
            'volume' => 'Volume',
            'duree' => 'Max length per video',
            'exports' => 'Exports',
            'mp3' => 'MP3 extraction',
            'historique' => 'History',
            'recherche' => 'History search',
            'attente' => 'Queue',
            'credits' => 'AI credits (script analysis)',
        ],
        'valeurs' => [
            'prix_mois' => ':montant per month or :an per year',
            'prix_gratuit' => ':montant',
            'prix_pack' => ':montant, one-time payment',
            'volume_gratuit' => ':nombre videos per month',
            'volume_pro' => ':heures h per month',
            'volume_pack' => ':heures h, valid for :mois months',
            'duree_gratuit' => ':minutes min (preview beyond)',
            'duree_payant' => ':heures h',
            'historique_gratuit' => ':jours days',
            'historique_illimite' => 'Unlimited',
            'attente_standard' => 'Standard',
            'attente_prioritaire' => 'Priority',
            'credits_pro' => 'Included, amount to be confirmed',
        ],
        'cta' => [
            'gratuit' => 'Start for free',
            'pro' => 'Go Pro',
            'pack' => 'Buy a Pack',
        ],
        'faq' => [
            'titre' => 'Pricing questions',
            'liste' => [
                [
                    'question' => 'What counts as one video on the Free plan?',
                    'reponse' => 'Each processed video counts as one, whatever its length. Beyond :minutes minutes, Voxia transcribes the first :minutes minutes and offers Pro for the full transcription.',
                ],
                [
                    'question' => 'How are Pro and Pack hours counted?',
                    'reponse' => 'On the actual length of the video, rounded up to the next minute. Technical failures never count.',
                ],
                [
                    'question' => 'Can I change plan or cancel?',
                    'reponse' => 'Yes, from the Subscription page: change plan, switch from monthly to yearly, or cancel. After cancelling, access stays open until the end of the paid period.',
                ],
                [
                    'question' => 'Does a Pack expire?',
                    'reponse' => 'Pack hours stay valid for :mois months after purchase.',
                ],
                [
                    'question' => 'How are subscriptions paid?',
                    'reponse' => 'Payments go through a payment provider, which handles VAT and invoices. Voxia never sees or stores your bank details.',
                ],
            ],
        ],
    ],

    'questions' => [
        'titre' => 'Frequently asked questions',
        'intro' => 'Answers to the most common questions. Missing one? Write to us from the Contact page.',
        'categories' => [
            [
                'titre' => 'Transcription',
                'liste' => [
                    [
                        'question' => 'What sources can I transcribe?',
                        'reponse' => 'A YouTube or TikTok link, or an audio or video file (:formats). Public videos are fetched; a private, deleted or region-blocked video is reported before anything is counted.',
                    ],
                    [
                        'question' => 'In which languages?',
                        'reponse' => 'Voxia detects the language automatically. You can also force it: :langues.',
                    ],
                    [
                        'question' => 'Which formats can I download?',
                        'reponse' => 'Plain text as .txt, subtitles as .srt and .vtt, and audio as .mp3. .pdf is included in paid plans.',
                    ],
                    [
                        'question' => 'What happens if a transcription fails?',
                        'reponse' => 'The reason is shown and the video is not counted against your quota. You can retry the link or upload the file directly.',
                    ],
                ],
            ],
            [
                'titre' => 'Account and plans',
                'liste' => [
                    [
                        'question' => 'Do I need an account to try it?',
                        'reponse' => 'Yes: a free account, with an email address verified before your first transcription.',
                    ],
                    [
                        'question' => 'How many videos can I transcribe for free?',
                        'reponse' => ':nombre videos per month, up to :minutes minutes each. The counter resets on the 1st of each month and stays visible at all times.',
                    ],
                    [
                        'question' => 'How long are my transcriptions kept?',
                        'reponse' => 'On the Free plan, transcriptions are kept for :jours days, with a reminder email :avant days before deletion. On paid plans, history is unlimited.',
                    ],
                    [
                        'question' => 'How do I delete my account?',
                        'reponse' => 'From your account settings. Deleting removes your account, transcriptions, exports and MP3 files.',
                    ],
                ],
            ],
            [
                'titre' => 'Data and privacy',
                'liste' => [
                    [
                        'question' => 'Are my transcriptions public?',
                        'reponse' => 'No. They are visible to you only, are not indexed, and are never shared by Voxia.',
                    ],
                    [
                        'question' => 'Can I get all my data back?',
                        'reponse' => 'Yes. From your settings, you request an export of all your data as a ZIP file: transcriptions, texts, MP3 files and account information. The download link arrives by email.',
                    ],
                    [
                        'question' => 'Does Voxia use audience-measurement cookies?',
                        'reponse' => 'No. Only necessary cookies are used (session, security, language choice). See the privacy policy.',
                    ],
                ],
            ],
        ],
    ],

    'conditions' => [
        'titre' => 'Terms of use',
        'sections' => [
            [
                'titre' => '1. Purpose and publisher',
                'paragraphes' => [
                    'These terms govern the use of Voxia, a service that transcribes videos and audio files. Voxia is published by :editeur (SIRET :siret), :adresse, :forme.',
                    'By creating an account or using the service, you accept these terms.',
                ],
            ],
            [
                'titre' => '2. Account',
                'paragraphes' => [
                    'You sign up with an email address and a password, then verify your address. You are responsible for keeping your credentials confidential and for activity on your account.',
                ],
            ],
            [
                'titre' => '3. Rights in videos and files',
                'paragraphes' => [
                    'You warrant that you hold all the rights needed for each video, link or file you submit to Voxia: you own it, you have the permission of its owner, or its use is permitted by law.',
                    'Voxia does not verify these rights. You alone are responsible for the content you submit and for the consequences of processing it.',
                ],
            ],
            [
                'titre' => '4. Prohibited uses',
                'paragraphes' => [
                    'You may not use Voxia to infringe third-party rights, bypass service limits, automate mass submissions, or resell transcriptions that do not belong to you.',
                ],
            ],
            [
                'titre' => '5. Private transcriptions',
                'paragraphes' => [
                    'No transcription is public: it is visible only from your account. Voxia does not publish, index or share it.',
                ],
            ],
            [
                'titre' => '6. Plans, quotas and payment',
                'paragraphes' => [
                    'Plans, quotas and prices are described on the Pricing page. Payments are handled by a payment provider: :prestataire.',
                    'A subscription can be cancelled at any time from the Subscription page; access stays open until the end of the paid period. A technical failure never counts against your quota.',
                ],
            ],
            [
                'titre' => '7. Retention',
                'paragraphes' => [
                    'On the Free plan, transcriptions are kept for :historique days. A reminder email is sent :avant days before deletion.',
                    'MP3 files and uploaded files are kept for :medias days, on all plans.',
                ],
            ],
            [
                'titre' => '8. Service availability',
                'paragraphes' => [
                    'The service relies on third-party platforms (YouTube, TikTok) and technical providers. Voxia does not guarantee permanent availability or the retrieval of every video.',
                ],
            ],
            [
                'titre' => '9. Ownership of content',
                'paragraphes' => [
                    'You keep ownership of your content and its transcriptions. You allow Voxia to process, store and return them only to provide the service, for the duration of retention.',
                ],
            ],
            [
                'titre' => '10. Liability',
                'paragraphes' => [
                    'Voxia is liable for direct damage within the limits permitted by law. Liability cap: :a_completer.',
                ],
            ],
            [
                'titre' => '11. Deletion and suspension',
                'paragraphes' => [
                    'You can delete your account and all your data from your settings. Voxia may suspend an account that does not comply with these terms.',
                ],
            ],
            [
                'titre' => '12. Governing law and disputes',
                'paragraphes' => [
                    'Governing law and competent courts: :a_confirmer.',
                ],
            ],
            [
                'titre' => '13. Contact',
                'paragraphes' => [
                    'For any question about these terms: :email.',
                ],
            ],
        ],
    ],

    'confidentialite' => [
        'titre' => 'Privacy policy',
        'sections' => [
            [
                'titre' => '1. Data controller',
                'paragraphes' => [
                    ':editeur (SIRET :siret), :adresse, :forme. Contact: :email.',
                ],
            ],
            [
                'titre' => '2. Data collected',
                'paragraphes' => [
                    'Account: name, email address, password (hashed, never stored in clear), language and plan.',
                    'Use: links and files you submit, titles, transcriptions, script analyses, MP3 files, quotas and technical processing logs.',
                    'Technical: IP address, to limit requests and prevent abuse, and the cookies described in section 4.',
                    'Payment: handled by the payment provider. Voxia does not store card numbers or bank details.',
                ],
            ],
            [
                'titre' => '3. Purposes and legal bases',
                'paragraphes' => [
                    'Providing the service, transcribing and exporting your content, managing your account and quotas: performance of the contract.',
                    'Preventing abuse and ensuring security: legitimate interest.',
                    'Invoicing and accounting obligations: legal obligation.',
                    'Sending service emails (verification, end of processing, deletion reminder, export): performance of the contract.',
                ],
            ],
            [
                'titre' => '4. Cookies and audience measurement',
                'paragraphes' => [
                    'Voxia uses only necessary cookies: session, form protection, language choice.',
                    'No audience-measurement tool and no advertising cookie is used. For that reason, no consent banner is displayed. If such a tool is added, this section and the banner will be updated beforehand.',
                ],
            ],
            [
                'titre' => '5. Sub-processors',
                'paragraphes' => [
                    'ElevenLabs: audio transcription.',
                    'Anthropic: analysis of advertising script structure (feature F7).',
                    'Payment provider (:prestataire): subscriptions, Packs, invoices.',
                    'Hosting (:a_confirmer), file storage (:a_confirmer), email delivery (:a_confirmer).',
                    'Download proxies (:a_confirmer): they receive the video address and the download request.',
                ],
            ],
            [
                'titre' => '6. Transfers outside the European Union',
                'paragraphes' => [
                    'Some sub-processors are established in the United States. Transfers are framed by the European Commission standard contractual clauses :a_confirmer.',
                ],
            ],
            [
                'titre' => '7. Retention periods',
                'paragraphes' => [
                    'Free plan history: :historique days, with a reminder :avant days before deletion.',
                    'MP3 and uploaded files: :medias days, on all plans.',
                    'Data export: the ZIP file is deleted at the latest :export hours after it is prepared.',
                    'Deleted account: all account data is erased.',
                ],
            ],
            [
                'titre' => '8. Your rights',
                'paragraphes' => [
                    'You can request access to your data, its correction or erasure, restriction of processing, or object to it.',
                    'You can download all your data as a ZIP from your account settings ("Your data"). Account deletion is also available on that page.',
                    'To exercise a right, write to :email. You can also contact the CNIL (cnil.fr).',
                ],
            ],
            [
                'titre' => '9. Security',
                'paragraphes' => [
                    'HTTPS connections, hashed passwords, files served through signed and temporary links, access limited to your own account.',
                ],
            ],
        ],
    ],

    'contact' => [
        'titre' => 'Contact',
        'intro' => 'A question, a bug, a billing issue? Write to us, we will reply as soon as we can.',
        'champs' => [
            'nom' => 'Your name',
            'email' => 'Your email address',
            'sujet' => 'Subject',
            'message' => 'Your message',
            'message_aide' => 'Do not send any password or bank details.',
        ],
        'sujets' => [
            'question' => 'A question',
            'bug' => 'A bug',
            'facturation' => 'Billing or subscription',
            'autre' => 'Other',
        ],
        'bouton' => 'Send message',
        'envoye' => 'Thanks, your message has been sent.',
        'indisponible' => 'The form is not open yet. Write to us at :email.',
        'trop_de_messages' => 'Too many messages sent from this connection. Please try again later.',
        'trop_rapide' => 'The form was sent too quickly. Please try again in a moment.',
        'miel' => 'Leave this field empty',
        'confidentialite' => 'Your message is sent to our team and handled under the privacy policy.',
    ],

    'contact_message' => [
        'objet' => 'Voxia contact: :sujet',
        'titre' => 'New message',
        'de' => 'From: :nom (:email)',
        'sujet' => 'Subject: :sujet',
    ],

    'export' => [
        'objet' => 'Your Voxia data is ready',
        'titre' => 'Export ready',
        'texte' => 'Hello :nom, your Voxia data export is ready: transcriptions, texts, MP3 files and account information, in a ZIP file.',
        'bouton' => 'Download my export',
        'expiration' => 'This link is valid for :minutes minutes, and the file is deleted at the latest :heures hours after it was prepared.',
        'ignorer' => 'You receive this message because you requested an export from your settings. If that was not you, change your password.',
    ],
];
