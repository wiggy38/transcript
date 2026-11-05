# Voxia — Cahier des charges du MVP

3 novembre 2026 — version éditable : https://claude.ai/artifact/RDgSzCvEwnsrtnTdTum94Q

## 1. Contexte et objectifs

Le MVP de Voxia permet de coller un lien YouTube ou TikTok, ou d'importer un fichier, et d'obtenir une transcription horodatée exportable et l'audio en MP3. Il couvre les besoins communs aux deux personas principaux, le créateur de contenu et le marketeur ou e-commerçant, ajoute l'analyse de script publicitaire pour les marketeurs, et lance les plans Gratuit, Pro et Pack.

Le MVP doit prouver trois choses :

- les créateurs et marketeurs paient pour transcrire à partir d'un lien, sans téléchargement manuel ;
- les deux déclencheurs d'upgrade du plan gratuit fonctionnent (6e vidéo du mois, aperçu limité à 10 minutes) ;
- le coût réel du téléchargement des vidéos (bande passante, proxys) reste compatible avec les prix de l'offre.

| Indicateur | Cible proposée (à valider) |
| --- | --- |
| Transcriptions réussies depuis un lien valide | 95 % ou plus |
| Délai médian pour une vidéo de 10 min | moins de 2 min |
| Conversion gratuit vers payant à 30 jours | 3 à 5 % des inscrits actifs |
| Coût moyen par heure de vidéo traitée | mesuré pendant la bêta, comparé à 0,22 $ de transcription |

## 2. Utilisateurs et parcours

Le MVP est conçu pour les personas 1 et 2 ; les personas 3 et 6 sont servis par le plan gratuit et le Pack, sans fonction dédiée.

| Persona | Ce qu'il fait dans le MVP | Plan visé |
| --- | --- | --- |
| 1. Créateur de contenu | Transcrit ses propres vidéos, exporte les sous-titres, extrait le MP3 pour un podcast | Pro |
| 2. Marketeur / e-commerçant | Transcrit des pubs TikTok et des vidéos concurrentes, voit la structure d'une pub découpée, retrouve un script dans son historique | Pro |
| 3. Étudiant / autodidacte | Transcrit un cours, clique sur un horodatage, exporte en PDF | Gratuit, Pack |
| 6. Apprenant en langues | Lit le texte d'une vidéo, télécharge l'audio | Gratuit, Pack |

**Parcours principal**

1. Le visiteur colle un lien sur la page d'accueil.
2. Il crée un compte et vérifie son email ; le lien collé est conservé.
3. Voxia affiche le titre, la durée et la miniature, puis vérifie le quota restant.
4. L'utilisateur lance le traitement et suit son statut ; il peut fermer la page.
5. Il lit la transcription horodatée et clique sur un horodatage pour réécouter le passage.
6. Il exporte le texte ou les sous-titres, ou télécharge le MP3.
7. Il retrouve la transcription plus tard dans son historique.

**Parcours d'upgrade** : à la 6e vidéo du mois, ou devant une vidéo de plus de 10 minutes en gratuit, l'utilisateur voit les plans Pro et Pack et peut payer sans quitter son travail en cours.

## 3. Périmètre

Le MVP comprend les six fonctionnalités marquées MVP dans la synthèse des personas, l'analyse de script publicitaire avancée de la V2, et le socle sans lequel elles ne peuvent pas être vendues.

| ID | Fonctionnalité MVP | Personas |
| --- | --- | --- |
| F1 | Transcription depuis un lien YouTube / TikTok ou un fichier | Tous |
| F2 | Transcription horodatée avec ponctuation propre | 1, 2, 3, 4 |
| F3 | Extraction de l'audio en MP3 | 1, 6 |
| F4 | Export TXT, PDF, SRT, VTT | 1, 3, 5 |
| F5 | Historique et recherche dans ses transcriptions | 1, 2, 3, 4 |
| F6 | Offre gratuite limitée | 3, 6 |
| F7 | Analyse de la structure d'un script publicitaire (avancée de la V2) | 2 |

**Socle nécessaire** (absent du tableau des personas) : comptes et vérification d'email, plans et quotas, paiement, page d'accueil et page des tarifs, back-office d'administration, CGU et politique de confidentialité.

**Hors MVP**

- V2 : résumé et points clés, réécriture en article ou post, bibliothèque classée par niche ou projet, plusieurs liens à la fois, traduction, plan Business et crédits IA.
- Plus tard : identification des intervenants, éditeur synchronisé avec l'audio, liste de vocabulaire.
- Aussi exclus : correction du texte dans Voxia, partage public d'une transcription, application mobile, API publique.

## 4. Exigences fonctionnelles

Chaque fonctionnalité est livrée quand tous ses critères d'acceptation sont vérifiés. F6 est détaillée en section 5 avec les plans.

### F1 — Transcription depuis un lien ou un fichier

- Liens acceptés : vidéos YouTube (y compris Shorts) et vidéos TikTok publiques.
- Fichiers acceptés : MP3, M4A, WAV, MP4, MOV, WEBM ; taille maximale proposée 2 Go.
- Avant lancement, Voxia récupère titre, durée, miniature et chaîne, puis vérifie la durée et le quota du plan.
- La langue est détectée par le moteur ; l'utilisateur peut la forcer dans une liste.
- Le traitement est asynchrone, avec les statuts En attente, Téléchargement, Transcription, Terminé, Échec.
- Un email prévient l'utilisateur quand une vidéo de plus de 15 minutes est prête.

Critères d'acceptation

- [ ] Un lien public valide de 10 minutes donne une transcription sans autre action que le lancement.
- [ ] Une vidéo privée, supprimée, bloquée dans la région ou un live en cours affiche un message clair et ne décompte rien.
- [ ] Un échec technique ne décompte rien du quota.
- [ ] Un lien déjà transcrit par le même utilisateur renvoie vers la transcription existante.

### F2 — Transcription horodatée avec ponctuation propre

- Le texte est ponctué, avec majuscules, et regroupé en paragraphes.
- Chaque paragraphe porte l'horodatage de son début ; l'affichage des horodatages peut être masqué.
- Un clic sur un horodatage lance la lecture à ce moment : lecteur YouTube intégré, ou lecteur audio sur le MP3 pour TikTok et les fichiers.
- Recherche dans la transcription ouverte, avec surlignage des résultats.
- Bouton « Copier le texte » en un clic.

Critères d'acceptation

- [ ] Le clic sur un horodatage positionne la lecture à 1 seconde près.
- [ ] Une transcription de 3 heures s'affiche et reste fluide au défilement.

### F3 — Extraction de l'audio en MP3

- Le MP3 est produit en même temps que la transcription, pour les liens comme pour les fichiers.
- Qualité proposée 192 kbit/s ; le fichier porte le titre de la vidéo.

Critères d'acceptation

- [ ] Le MP3 téléchargé a la même durée que la vidéo source, à 1 seconde près.
- [ ] Le nom du fichier ne contient que des caractères valides sous Windows, macOS et Linux.

### F4 — Export TXT, PDF, SRT, VTT

- TXT et PDF : avec ou sans horodatages ; le PDF reprend le titre, le lien source et la date de transcription.
- SRT et VTT : 42 caractères maximum par ligne, 2 lignes maximum, durée de 1 à 7 secondes par sous-titre.
- Tous les exports sont en UTF-8. Les formats disponibles dépendent du plan (section 5).

Critères d'acceptation

- [ ] Les fichiers SRT et VTT s'importent sans erreur dans YouTube Studio et CapCut.
- [ ] Les accents et caractères non latins s'affichent correctement dans chaque format.

### F5 — Historique et recherche

- Liste des transcriptions : miniature, titre, plateforme, durée, date, statut ; tri par date.
- Recherche plein texte dans toutes ses transcriptions (Pro et Pack), avec extrait et lien vers l'horodatage.
- Suppression d'une transcription par l'utilisateur.
- Plan gratuit : transcriptions conservées 30 jours, avec rappel 3 jours avant suppression.

Critères d'acceptation

- [ ] Une recherche sur 500 transcriptions répond en moins d'une seconde.
- [ ] Une transcription supprimée disparaît de la liste et de la recherche.

### F7 — Analyse de la structure d'un script publicitaire

- Sur une transcription, un bouton « Analyser le script » ; pensé pour les vidéos courtes (pubs TikTok, Reels), durée maximale proposée 3 minutes.
- Découpage en cinq parties : accroche, problème, preuve, offre, appel à l'action, chacune avec son début, sa fin et son texte.
- Affichage repris de la maquette : une barre proportionnelle à la durée de chaque partie, puis le texte de chaque partie avec ses horodatages.
- Une partie absente de la pub est marquée « non détectée », jamais inventée.
- L'analyse est rattachée à sa transcription dans l'historique et la recherche, et s'exporte en TXT et PDF.
- Chaque analyse consomme 1 crédit IA (section 5).

Critères d'acceptation

- [ ] Sur 20 pubs découpées à la main, les limites des parties tombent à 2 secondes près dans 80 % des cas (cible à valider).
- [ ] Les parties ne se chevauchent pas et suivent l'ordre de la vidéo.
- [ ] L'analyse d'une pub de 60 secondes s'affiche en moins de 15 secondes.
- [ ] Un clic sur une partie lance la lecture à son début.

## 5. Comptes, plans, quotas et paiement

Trois plans sont vendus au lancement ; le plan Business arrive avec la V2 et n'est pas développé dans le MVP.

| | Gratuit | Pro | Pack |
| --- | --- | --- | --- |
| Prix | 0 $ | 12 $/mois ou 96 $/an | 5 $, paiement unique |
| Volume | 5 vidéos/mois | 30 h/mois | 10 h, valables 6 mois |
| Durée max par vidéo | 10 min (aperçu au-delà) | 3 h | 3 h |
| Exports | TXT, SRT | TXT, PDF, SRT, VTT | TXT, PDF, SRT, VTT |
| MP3 | Oui | Oui | Oui |
| Historique | 30 jours | Illimité + recherche | Illimité + recherche |
| File d'attente | Standard | Prioritaire | Standard |

**Crédits IA** : l'analyse de script (F7) consomme 1 crédit par analyse, compté à part des heures de transcription. Le Pro inclut un nombre de crédits par mois, encore à fixer ; le Gratuit et le Pack n'y ont pas accès, comme prévu dans l'offre.

**Comptes**

- Inscription par email et mot de passe, avec vérification de l'email avant la première transcription.
- Connexion Google proposée en option (à valider).
- Limite de comptes gratuits par appareil ou par adresse IP pour freiner les comptes multiples.
- Suppression du compte et de toutes ses données depuis les réglages.

**Décompte des quotas**

- Gratuit : une vidéo traitée = 1 vidéo, quelle que soit sa durée ; le compteur repart le 1er du mois.
- Pro et Pack : durée réelle de la vidéo, arrondie à la minute supérieure ; le Pro repart à la date anniversaire de l'abonnement.
- Vidéo de plus de 10 minutes en gratuit : les 10 premières minutes sont transcrites, 1 vidéo est décomptée, et l'écran affiche « Passe au Pro pour la transcription complète ».
- Le compteur de vidéos ou d'heures restantes est visible en permanence.

**Paiement**

- Abonnement Pro mensuel ou annuel, Pack en paiement unique ; prix en dollars US.
- Passage du Gratuit au Pro sans perdre la transcription en cours d'aperçu : elle est relancée en version complète après paiement.
- Gestion de l'abonnement en libre-service : changement de formule, résiliation, factures.

**Back-office d'administration**

- Liste des utilisateurs, de leur plan et de leur consommation.
- Liste des traitements avec statut, motif d'échec et coût estimé.
- Ajustement manuel d'un quota et remboursement d'un traitement raté.

## 6. Exigences non fonctionnelles

Les cibles ci-dessous sont des propositions à confirmer après les premiers tests de charge.

| Domaine | Exigence |
| --- | --- |
| Performance | Vidéo de 10 min traitée en moins de 2 min (médiane) ; pages chargées en moins de 2 s |
| Charge | 20 traitements simultanés au lancement, sans dégradation ; files Pro servies en priorité |
| Disponibilité | 99,5 % par mois hors maintenance annoncée ; sauvegarde quotidienne de la base |
| Sécurité | HTTPS partout, mots de passe hachés, limitation du nombre de requêtes, fichiers accessibles par liens signés et temporaires |
| Données personnelles | Conformité RGPD (cible incluant l'UE) : consentement, export et suppression des données, liste des sous-traitants dont ElevenLabs |
| Interface | Responsive, utilisable sur mobile ; interface en anglais et en français au lancement (proposition) |
| Direction visuelle | Direction « Surligneur » retenue (remplace Signal) ; charte et structure en section 7 |
| Observabilité | Journal de chaque traitement : durée de chaque étape, motif d'échec, plateforme, coût estimé |
| Accessibilité | Contrastes suffisants, navigation au clavier, lecteur et transcription utilisables au lecteur d'écran |

## 7. Direction retenue : « Surligneur »

La direction « Surligneur » (Claude Design, proposition 3) remplace la maquette « Signal », dont la charte n'est plus appliquée. Elle est épurée, vivante et professionnelle : papier clair par défaut, encre presque noire, un surligneur jaune-vert qui marque la phrase lue, les mots d'accent et le focus, et un orange plus profond pour les actions. Source : `docs/Voxia Direction 3 - Surligneur.dc.html` (tokens, composants et animations). Les écrans de l'application reprennent cette charte et ses composants.

**Charte**

| Élément | Valeur (clair · sombre) |
| --- | --- |
| Fond | #F4F1EA · #13120F |
| Surfaces | #FFFDF8 · #1B1A16 ; surface élevée #EAE6DB · #24231E |
| Filets | décoratif #DDD7CA · #34322B ; contour de champ ou de bouton #817A6B · #77725F (3:1 minimum) |
| Texte | principal #1A1814 · #F2EEE4 ; secondaire #4B463D · #C4BEB0 ; discret #655F53 · #9C9686 (4,5:1 minimum) |
| Accent d'action | #E4521F · #FF7A4D, avec texte encre #1A1814 · #13120F sur bouton ; texte d'accent #B23A0E · #FF8A5C |
| Surligneur | #DDF37A (surlignage de lecture) · #3A4419 ; mot d'accent #D7F46A sur encre #1A1814 (14,4:1) |
| États | succès #1D7444 · #5DD394 ; avertissement #875700 · #F0B64A ; erreur #BA2530 · #FF8077 |
| Focus | #1A1814 · #D7F46A |
| Typographies | Google Fonts, auto-hébergées (fontsource) : Roboto 400, 500 et 700 pour le texte et les titres ; Roboto Mono pour horodatages, durées et données, en casse normale. Les mots d'accent gardent la police du titre, surlignés |
| Rayons | pilule (999 px) boutons et badges ; 12 px champs ; 20 px panneaux |
| Icônes | Phosphor (MIT), graisse Regular, 22 px ; Duotone pour les états vides |
| Thème | clair par défaut ; sombre en option ; suit `prefers-color-scheme` en repli |
| Animations | onde audio (1,6 s), progression (déterminée pour l'envoi, indéterminée pour les étapes), apparition mot à mot sur l'accueil ; toutes coupées quand l'utilisateur réduit les animations |

Anciennes valeurs (Signal, §7 de la version précédente) : fond #0E0F0E, accent #FF6A3D, Instrument Serif, rayons 4-6-10 px. Elles ne sont plus utilisées.

**Structure de la page d'accueil**

1. En-tête : Usages, Fonctionnement, Tarifs, Se connecter, Essayer gratuitement.
2. Hero : « Chaque vidéo contient un texte. Voxia te l'écrit. », champ de lien et bouton « Transcrire → », lien de dépôt de fichier.
3. Aperçu produit : onde audio, transcription horodatée avec la phrase en cours surlignée, boutons Copier, .txt, .srt, .vtt, .mp3.
4. Bandeau défilant d'extraits horodatés.
5. Usages : créateurs et marketeurs.
6. Fonctionnement en 4 étapes : lien, audio, transcription, export.
7. Fiche technique : entrées, sorties, horodatage, langues.
8. Tarifs.
9. Questions fréquentes.
10. Pied de page : confidentialité, conditions, contact.

**Ce que la maquette impose au MVP**

- Le champ de lien du hero démarre le parcours principal : le lien collé est conservé pendant l'inscription (section 2, étape 2).
- L'aperçu produit sert de modèle à l'écran de transcription (F2) : horodatages en Geist Mono, phrase en cours surlignée, barre d'export sous le texte.
- La promesse « en une ou deux minutes » du hero doit tenir sur une vidéo de 10 minutes (cible de la section 1).

**Écarts à trancher avant l'intégration**

| Point | Maquette | Cahier des charges et offre | Proposition |
| --- | --- | --- | --- |
| Carte Marketeurs | Script découpé : accroche, problème, preuve, offre, appel à l'action | Analyse de script intégrée au MVP (F7) | Tranché : carte gardée telle quelle |
| Carte Créateurs | « Une vidéo, quatre contenus » : article, newsletter | Réécriture prévue en V2 ; le MVP exporte le texte | Reformuler en « une transcription, quatre formats » |
| Devise | Prix en € | Prix en $ | Passer en $ |
| Plans affichés | Gratuit et Pro, valeurs entre crochets | Gratuit 5 vidéos de 10 min max ; Pro 12 $/mois ; Pack 5 $ | Remplir les valeurs et ajouter le Pack |
| MP3 | Présenté comme un avantage Pro | Inclus dans tous les plans | Pro : « tous les exports, dont PDF et VTT » |
| Fichiers acceptés | MP4, MOV, MP3 | MP3, M4A, WAV, MP4, MOV, WEBM | Aligner la liste de la page |
| Langues et conservation | Valeurs à confirmer | Points ouverts (section 9) | Remplir une fois tranchés |
| Langue de l'interface | Français, tutoiement | Anglais et français proposés | Rédiger la version anglaise |

## 8. Architecture technique

Chaque vidéo devient une tâche de fond : l'application la met en file, un worker la télécharge, extrait le MP3, l'envoie à ElevenLabs, puis enregistre le texte et les fichiers.

Chaîne de traitement : Navigateur → Application Laravel (comptes, quotas, paiements) → File d'attente (Redis + Laravel Horizon, 1 tâche par vidéo) → Téléchargement (yt-dlp via proxys) → Extraction audio (FFmpeg, MP3) → Transcription (API ElevenLabs Scribe v2) → Base de données (comptes, transcriptions) et stockage objet (MP3 et fichiers d'export). Le téléchargement est l'étape fragile et coûteuse de la chaîne.

La page de l'utilisateur interroge le statut de la tâche jusqu'à la fin ; aucune requête web n'attend le traitement.

| Composant | Choix | Statut |
| --- | --- | --- |
| Application web | Laravel (PHP) | Validé |
| Transcription | ElevenLabs Scribe v2, environ 0,22 $ par heure d'audio | Validé |
| File d'attente | Redis + Laravel Horizon | Proposé |
| Téléchargement | yt-dlp, proxys résidentiels | Proposé |
| Audio | FFmpeg | Proposé |
| Base de données | MySQL ou PostgreSQL | Proposé |
| Stockage des fichiers | Stockage objet compatible S3 | Proposé |
| Paiement | Laravel Cashier avec Stripe ou Paddle | À décider |
| Emails transactionnels | Vérification de compte, notifications de fin de traitement | À décider |

F7 ajoute une étape après la transcription : un modèle de langage, appelé par API, reçoit le texte horodaté et renvoie les cinq parties dans un format structuré. Le fournisseur reste à choisir et le coût par analyse sera mesuré pendant la bêta.

## 9. Risques, points ouverts et planning

Le risque principal est technique et juridique : la récupération des vidéos YouTube et TikTok, pas la transcription.

| Risque | Parade prévue |
| --- | --- |
| YouTube ou TikTok bloque les téléchargements | yt-dlp mis à jour automatiquement, proxys, alerte si le taux d'échec dépasse 5 %, import de fichier toujours possible |
| Conditions d'utilisation des plateformes | Avis juridique avant lancement ; CGU où l'utilisateur garantit ses droits ; aucune transcription publique |
| Coût des proxys supérieur aux prévisions | Coût mesuré par traitement dès la bêta ; durée maximale par vidéo ajustable |
| Abus du plan gratuit | Vérification d'email, limite par appareil ou IP |
| Dépendance à ElevenLabs | Moteur appelé derrière une interface interne, pour pouvoir basculer vers un autre fournisseur |
| Paiement depuis le Burkina Faso | Stripe ne semble pas ouvert aux entreprises du Burkina Faso (à vérifier) ; un marchand officiel comme Paddle ou Lemon Squeezy gère aussi la TVA internationale |

**Points ouverts**

- [ ] L'extraction MP3 seule, sans transcription, décompte-t-elle une vidéo en gratuit ?
- [ ] Durée de conservation des MP3 et des fichiers importés (proposition : 30 jours).
- [ ] Taille maximale d'un fichier importé (proposition : 2 Go).
- [ ] Prestataire de paiement et structure juridique qui encaisse.
- [ ] Langues de l'interface au lancement (proposition : anglais et français).
- [ ] Autoriser un premier essai sans compte depuis la page d'accueil ?
- [ ] Nombre de crédits IA inclus chaque mois dans le Pro ; une analyse d'essai en Gratuit pour déclencher l'upgrade ?
- [ ] Fournisseur du modèle de langage utilisé pour F7.
- [ ] Durée maximale d'une vidéo analysable (proposition : 3 minutes).

**Planning indicatif** (à ajuster selon l'équipe) — l'ordre commence par l'étape la plus risquée :

1. Chaîne de traitement : lien, téléchargement, MP3, transcription, en ligne de commande — 2 à 3 semaines.
2. Comptes, historique et affichage de la transcription (F2, F5) — 2 semaines.
3. Exports (F4), lecteur synchronisé aux horodatages et analyse de script (F7) — 2 à 3 semaines.
4. Plans, quotas, paiement et back-office (F6) — 2 semaines.
5. Page d'accueil, page des tarifs, CGU, puis bêta privée avec 20 à 50 créateurs et marketeurs — 2 semaines.
