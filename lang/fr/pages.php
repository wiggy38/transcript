<?php

// Pages publiques : tarifs, questions fréquentes, mentions, confidentialité, contact.
// Les chiffres (quotas, durées, prix, conservation) ne sont pas écrits ici : ils viennent de
// config/voxia.php et sont passés en remplacements (:nom). Les textes juridiques sont des
// BROUILLONS à faire relire par un juriste avant lancement (CLAUDE.md §7).

return [

    'brouillon' => 'BROUILLON — à faire relire par un juriste avant lancement',
    'maj' => 'Brouillon daté du :date. Les valeurs entre crochets restent à compléter.',
    'a_completer' => '[À COMPLÉTER]',
    'a_confirmer' => '[À CONFIRMER]',
    'forme' => 'forme juridique :statut',
    'email_manquant' => '[EMAIL DE CONTACT À COMPLÉTER]',

    'tarifs' => [
        'titre' => 'Tarifs',
        'intro' => 'Trois formules au lancement. Les prix sont en dollars US, les volumes sont annoncés en heures claires.',
        'oui' => 'Oui',
        'non' => 'Non',
        'colonnes' => [
            'fonctionnalite' => 'Ce qui est inclus',
        ],
        'lignes' => [
            'prix' => 'Prix',
            'volume' => 'Volume',
            'duree' => 'Durée max par vidéo',
            'exports' => 'Exports',
            'mp3' => 'Extraction MP3',
            'historique' => 'Historique',
            'recherche' => 'Recherche dans l\'historique',
            'attente' => 'File d\'attente',
            'credits' => 'Crédits IA (analyse de script)',
        ],
        'valeurs' => [
            'prix_mois' => ':montant par mois ou :an par an',
            'prix_gratuit' => ':montant',
            'prix_pack' => ':montant, paiement unique',
            'volume_gratuit' => ':nombre vidéos par mois',
            'volume_pro' => ':heures h par mois',
            'volume_pack' => ':heures h, valables :mois mois',
            'duree_gratuit' => ':minutes min (aperçu au-delà)',
            'duree_payant' => ':heures h',
            'historique_gratuit' => ':jours jours',
            'historique_illimite' => 'Illimité',
            'attente_standard' => 'Standard',
            'attente_prioritaire' => 'Prioritaire',
            'credits_pro' => 'Inclus, volume à confirmer',
        ],
        'cta' => [
            'gratuit' => 'Commencer gratuitement',
            'pro' => 'Passer au Pro',
            'pack' => 'Acheter un Pack',
        ],
        'faq' => [
            'titre' => 'Questions sur les tarifs',
            'liste' => [
                [
                    'question' => 'Que compte comme une vidéo dans le plan Gratuit ?',
                    'reponse' => 'Chaque vidéo traitée compte pour une, quelle que soit sa durée. Au-delà de :minutes minutes, Voxia transcrit les :minutes premières minutes et te propose le Pro pour la transcription complète.',
                ],
                [
                    'question' => 'Comment sont décomptées les heures du Pro et du Pack ?',
                    'reponse' => 'Sur la durée réelle de la vidéo, arrondie à la minute supérieure. Les échecs techniques ne décomptent rien.',
                ],
                [
                    'question' => 'Puis-je changer de formule ou résilier ?',
                    'reponse' => 'Oui, depuis l\'espace Abonnement : changement de formule, passage du mensuel à l\'annuel, résiliation. Après une résiliation, l\'accès reste ouvert jusqu\'à la fin de la période payée.',
                ],
                [
                    'question' => 'Le Pack expire-t-il ?',
                    'reponse' => 'Les heures du Pack restent valables :mois mois après l\'achat.',
                ],
                [
                    'question' => 'Comment sont payés les abonnements ?',
                    'reponse' => 'Les paiements passent par un prestataire de paiement, qui gère la TVA et les factures. Voxia ne voit ni ne conserve tes coordonnées bancaires.',
                ],
            ],
        ],
    ],

    'questions' => [
        'titre' => 'Questions fréquentes',
        'intro' => 'Les réponses aux questions les plus courantes. Une question manque ? Écris-nous depuis la page Contact.',
        'categories' => [
            [
                'titre' => 'Transcription',
                'liste' => [
                    [
                        'question' => 'Quelles sources puis-je transcrire ?',
                        'reponse' => 'Un lien YouTube ou TikTok, ou un fichier audio ou vidéo (:formats). Les vidéos publiques sont récupérées ; une vidéo privée, supprimée ou bloquée dans ta région est signalée avant tout décompte.',
                    ],
                    [
                        'question' => 'Dans quelles langues ?',
                        'reponse' => 'Voxia détecte la langue automatiquement. Tu peux aussi la forcer : :langues.',
                    ],
                    [
                        'question' => 'Quels formats puis-je télécharger ?',
                        'reponse' => 'Le texte en .txt, les sous-titres en .srt et .vtt, et l\'audio en .mp3. Le .pdf est inclus dans les plans payants.',
                    ],
                    [
                        'question' => 'Que se passe-t-il si la transcription échoue ?',
                        'reponse' => 'Le motif est affiché et la vidéo n\'est pas décomptée de ton quota. Tu peux relancer le lien ou importer le fichier directement.',
                    ],
                ],
            ],
            [
                'titre' => 'Compte et plans',
                'liste' => [
                    [
                        'question' => 'Faut-il un compte pour essayer ?',
                        'reponse' => 'Oui : un compte gratuit, avec une adresse email vérifiée avant la première transcription.',
                    ],
                    [
                        'question' => 'Combien de vidéos puis-je transcrire gratuitement ?',
                        'reponse' => ':nombre vidéos par mois, de :minutes minutes au plus. Le compteur repart le 1er de chaque mois et reste visible en permanence.',
                    ],
                    [
                        'question' => 'Combien de temps mes transcriptions sont-elles gardées ?',
                        'reponse' => 'Dans le plan Gratuit, les transcriptions sont conservées :jours jours, avec un email de rappel :avant jours avant leur suppression. Dans les plans payants, l\'historique est illimité.',
                    ],
                    [
                        'question' => 'Comment supprimer mon compte ?',
                        'reponse' => 'Depuis les réglages du compte. La suppression retire ton compte, tes transcriptions, tes exports et tes fichiers MP3.',
                    ],
                ],
            ],
            [
                'titre' => 'Données et confidentialité',
                'liste' => [
                    [
                        'question' => 'Mes transcriptions sont-elles publiques ?',
                        'reponse' => 'Non. Elles ne sont visibles que par toi, ne sont pas indexées et ne sont jamais partagées par Voxia.',
                    ],
                    [
                        'question' => 'Puis-je récupérer toutes mes données ?',
                        'reponse' => 'Oui. Depuis les réglages, tu demandes un export de toutes tes données en ZIP : transcriptions, textes, MP3 et informations du compte. Le lien arrive par email.',
                    ],
                    [
                        'question' => 'Voxia utilise-t-il des cookies de mesure d\'audience ?',
                        'reponse' => 'Non. Seuls les cookies nécessaires sont utilisés (session, sécurité, choix de langue). Voir la politique de confidentialité.',
                    ],
                ],
            ],
        ],
    ],

    'conditions' => [
        'titre' => 'Conditions générales d\'utilisation',
        'sections' => [
            [
                'titre' => '1. Objet et éditeur',
                'paragraphes' => [
                    'Ces conditions régissent l\'usage de Voxia, service de transcription de vidéos et de fichiers audio. Voxia est édité par :editeur (SIRET :siret), :adresse, :forme.',
                    'En créant un compte ou en utilisant le service, tu acceptes ces conditions.',
                ],
            ],
            [
                'titre' => '2. Compte',
                'paragraphes' => [
                    'L\'inscription se fait par adresse email et mot de passe, puis par la vérification de ton adresse. Tu es responsable de la confidentialité de tes identifiants et de l\'activité de ton compte.',
                ],
            ],
            [
                'titre' => '3. Droits sur les vidéos et fichiers',
                'paragraphes' => [
                    'Tu garantis que tu disposes de tous les droits nécessaires sur chaque vidéo, lien ou fichier que tu transmets à Voxia : tu en es titulaire, tu as l\'autorisation de son titulaire, ou son usage est permis par la loi.',
                    'Voxia ne vérifie pas ces droits. Tu es seul responsable des contenus que tu transmets et des conséquences de leur traitement.',
                ],
            ],
            [
                'titre' => '4. Usages interdits',
                'paragraphes' => [
                    'Tu ne peux pas utiliser Voxia pour porter atteinte aux droits de tiers, contourner les limites du service, automatiser des envois massifs ou revendre des transcriptions qui ne t\'appartiennent pas.',
                ],
            ],
            [
                'titre' => '5. Transcriptions privées',
                'paragraphes' => [
                    'Aucune transcription n\'est publique : elle n\'est visible que depuis ton compte. Voxia ne la publie, ne l\'indexe et ne la partage pas.',
                ],
            ],
            [
                'titre' => '6. Plans, quotas et paiement',
                'paragraphes' => [
                    'Les plans, quotas et prix sont décrits sur la page Tarifs. Les paiements sont traités par un prestataire de paiement :prestataire.',
                    'Un abonnement se résilie à tout moment depuis l\'espace Abonnement ; l\'accès reste ouvert jusqu\'à la fin de la période payée. Un échec technique ne décompte aucun quota.',
                ],
            ],
            [
                'titre' => '7. Conservation',
                'paragraphes' => [
                    'Dans le plan Gratuit, les transcriptions sont conservées :historique jours. Un email de rappel est envoyé :avant jours avant la suppression.',
                    'Les fichiers MP3 et les fichiers importés sont conservés :medias jours, pour tous les plans.',
                ],
            ],
            [
                'titre' => '8. Disponibilité du service',
                'paragraphes' => [
                    'Le service dépend de plateformes tierces (YouTube, TikTok) et de prestataires techniques. Voxia ne garantit ni une disponibilité permanente, ni la récupération de chaque vidéo.',
                ],
            ],
            [
                'titre' => '9. Propriété des contenus',
                'paragraphes' => [
                    'Tu restes propriétaire de tes contenus et de leurs transcriptions. Tu autorises Voxia à les traiter, les stocker et les restituer uniquement pour fournir le service, pendant la durée de conservation.',
                ],
            ],
            [
                'titre' => '10. Responsabilité',
                'paragraphes' => [
                    'Voxia répond des dommages directs dans les limites permises par la loi. Plafond de responsabilité : :a_completer.',
                ],
            ],
            [
                'titre' => '11. Suppression et suspension',
                'paragraphes' => [
                    'Tu peux supprimer ton compte et toutes tes données depuis les réglages. Voxia peut suspendre un compte qui ne respecte pas ces conditions.',
                ],
            ],
            [
                'titre' => '12. Droit applicable et litiges',
                'paragraphes' => [
                    'Droit applicable et juridiction compétente : :a_confirmer.',
                ],
            ],
            [
                'titre' => '13. Contact',
                'paragraphes' => [
                    'Pour toute question sur ces conditions : :email.',
                ],
            ],
        ],
    ],

    'confidentialite' => [
        'titre' => 'Politique de confidentialité',
        'sections' => [
            [
                'titre' => '1. Responsable du traitement',
                'paragraphes' => [
                    ':editeur (SIRET :siret), :adresse, :forme. Contact : :email.',
                ],
            ],
            [
                'titre' => '2. Données collectées',
                'paragraphes' => [
                    'Compte : nom, adresse email, mot de passe (haché, jamais en clair), langue et plan.',
                    'Usage : liens et fichiers transmis, titres, transcriptions, analyses de script, fichiers MP3, quotas et journaux techniques de traitement.',
                    'Technique : adresse IP, pour limiter les requêtes et prévenir les abus, ainsi que les cookies décrits à la section 4.',
                    'Paiement : géré par le prestataire de paiement. Voxia ne conserve ni numéro de carte ni coordonnées bancaires.',
                ],
            ],
            [
                'titre' => '3. Finalités et bases légales',
                'paragraphes' => [
                    'Fournir le service, transcrire et exporter tes contenus, gérer ton compte et tes quotas : exécution du contrat.',
                    'Prévenir les abus et assurer la sécurité : intérêt légitime.',
                    'Facturer et tenir les obligations comptables : obligation légale.',
                    'T\'envoyer les emails liés au service (vérification, fin de traitement, rappel de suppression, export) : exécution du contrat.',
                ],
            ],
            [
                'titre' => '4. Cookies et mesure d\'audience',
                'paragraphes' => [
                    'Voxia utilise uniquement des cookies nécessaires : session, protection des formulaires, choix de langue.',
                    'Aucun outil de mesure d\'audience et aucun cookie publicitaire n\'est utilisé. Aucun bandeau de consentement n\'est donc affiché. Si un tel outil est ajouté, cette section et le bandeau seront mis à jour avant.',
                ],
            ],
            [
                'titre' => '5. Sous-traitants',
                'paragraphes' => [
                    'ElevenLabs : transcription de l\'audio.',
                    'Anthropic : analyse de la structure des scripts (fonction F7).',
                    'Prestataire de paiement (:prestataire) : abonnements, Packs, factures.',
                    'Hébergeur (:a_confirmer), stockage des fichiers (:a_confirmer), envoi des emails (:a_confirmer).',
                    'Proxys de téléchargement (:a_confirmer) : ils reçoivent l\'adresse de la vidéo et la requête de téléchargement.',
                ],
            ],
            [
                'titre' => '6. Transferts hors de l\'Union européenne',
                'paragraphes' => [
                    'Certains sous-traitants sont établis aux États-Unis. Les transferts sont encadrés par les clauses contractuelles types de la Commission européenne :a_confirmer.',
                ],
            ],
            [
                'titre' => '7. Durées de conservation',
                'paragraphes' => [
                    'Historique du plan Gratuit : :historique jours, avec un rappel :avant jours avant la suppression.',
                    'MP3 et fichiers importés : :medias jours, pour tous les plans.',
                    'Export de tes données : le fichier ZIP est supprimé au plus tard :export heures après sa préparation.',
                    'Compte supprimé : toutes les données du compte sont effacées.',
                ],
            ],
            [
                'titre' => '8. Tes droits',
                'paragraphes' => [
                    'Tu peux demander l\'accès à tes données, leur rectification, leur effacement, la limitation ou l\'opposition à leur traitement.',
                    'Tu peux demander la portabilité de tes données : les récupérer toutes en ZIP depuis les réglages du compte (« Vos données »). La suppression du compte se fait depuis la même page.',
                    'Pour exercer un droit, écris à :email. Tu peux aussi saisir la CNIL (cnil.fr).',
                ],
            ],
            [
                'titre' => '9. Sécurité',
                'paragraphes' => [
                    'Connexion HTTPS, mots de passe hachés, fichiers servis par des liens signés et temporaires, accès limité à ton seul compte.',
                ],
            ],
        ],
    ],

    'contact' => [
        'titre' => 'Contact',
        'intro' => 'Une question, un bug, une facturation ? Écris-nous, nous te répondrons dès que possible.',
        'champs' => [
            'nom' => 'Ton nom',
            'email' => 'Ton adresse email',
            'sujet' => 'Sujet',
            'message' => 'Ton message',
            'message_aide' => 'Ne donne pas de mot de passe ni de coordonnées bancaires.',
        ],
        'sujets' => [
            'question' => 'Une question',
            'bug' => 'Un bug',
            'facturation' => 'Facturation ou abonnement',
            'autre' => 'Autre',
        ],
        'bouton' => 'Envoyer le message',
        'envoye' => 'Merci, ton message est envoyé.',
        'indisponible' => 'Le formulaire n\'est pas encore ouvert. Écris-nous à :email.',
        'trop_de_messages' => 'Trop de messages envoyés depuis cette connexion. Réessaie plus tard.',
        'trop_rapide' => 'Le formulaire a été envoyé trop vite. Réessaie dans un instant.',
        'miel' => 'Laisse ce champ vide',
        'confidentialite' => 'Ton message est envoyé à notre équipe et traité selon la politique de confidentialité.',
    ],

    'contact_message' => [
        'objet' => 'Contact Voxia : :sujet',
        'titre' => 'Nouveau message',
        'de' => 'De : :nom (:email)',
        'sujet' => 'Sujet : :sujet',
    ],

    'export' => [
        'objet' => 'Tes données Voxia sont prêtes',
        'titre' => 'Export prêt',
        'texte' => 'Bonjour :nom, ton export de données Voxia est prêt : transcriptions, textes, MP3 et informations du compte, dans un fichier ZIP.',
        'bouton' => 'Télécharger mon export',
        'expiration' => 'Ce lien est valable :minutes minutes, et le fichier est supprimé au plus tard :heures heures après sa préparation.',
        'ignorer' => 'Tu reçois ce message parce que tu as demandé un export depuis les réglages. Si ce n\'est pas toi, change ton mot de passe.',
    ],
];
