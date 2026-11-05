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
| Direction visuelle | Direction « Nocturne » retenue ; charte et structure en section 7 |
| Observabilité | Journal de chaque traitement : durée de chaque étape, motif d'échec, plateforme, coût estimé |
| Accessibilité | Contrastes suffisants, navigation au clavier, lecteur et transcription utilisables au lecteur d'écran |

## 7. Direction retenue : « Nocturne »

La direction « Nocturne » remplace la charte précédente. Elle est sombre par défaut : nuit indigo, vert citron pour les actions, cyan pour ce qui est en direct (lecture, traitement en cours). Un thème clair complet utilise les mêmes noms de jetons. Source : `docs/design_handoff_voxia_nocturne/` (README, `tokens/voxia-tokens.css`, `tokens/app.css`, maquettes `designs/`). Le handoff fait foi pour les valeurs ; `resources/css/voxia-tokens.css` en est l'intégration.

**Charte**

| Élément | Valeur (sombre par défaut · clair) |
| --- | --- |
| Fond | #0D0B1A · #F6F5FF |
| Surfaces | #15122A · #FFFFFF ; surface élevée #1D1938 · #ECEAFB |
| Filets | décoratif #2C2750 · #DDDAF2 ; contour de champ ou de bouton #6E68A0 · #7C77A6 (3:1 minimum) |
| Texte | principal #EEEBFF · #14112B ; secondaire #C0BAE6 · #423D66 ; discret #9690C4 · #5C5785 (4,5:1 minimum) |
| Accent d'action | #C8F25A · #4A6A00, avec texte encre #0D0B1A · #FFFFFF sur bouton ; texte d'accent identique |
| En direct et focus | cyan #4FD1E0 · #0E7C8A (en clair, le focus reprend l'olive #4A6A00) |
| Surlignage de lecture | ligne #2A2152 · #EEF8C8 ; horodatage de la ligne #4FD1E0 · #3E5700 |
| États | succès #4FE0A6 · #157A4F ; avertissement #FFC266 · #8A5A00 ; erreur #FF7A7A · #C0262D |
| Typographies | Auto-hébergées (fontsource) : Onest (texte, variable) ; Unbounded 600 (titres seulement, jamais sous 17 px) ; JetBrains Mono 400 et 500 (horodatages, formats, URL, métadonnées, `tabular-nums`) |
| Rayons | 12 px boutons et champs ; 20 px panneaux et cartes ; pilule (999 px) badges et pastilles |
| Ombres | `--vx-shadow` pour les panneaux ; `--vx-shadow-hero` pour le formulaire du héros |
| Icônes | Tabler Icons (MIT) ou Lucide (ISC), trait 1,6 à 1,8 px, `currentColor` ; aucune bibliothèque payante |
| Thème | sombre par défaut, sans repli sur le thème du système ; clair sous `data-theme="light"` (bascule utilisateur à prévoir, P139) |
| Animations | onde audio (1,6 s), point « en direct », étape en cours, apparition mot à mot de l'aperçu, scènes d'usage de la landing ; toutes coupées sous `prefers-reduced-motion` |

**Contraintes**

- Contraste du texte d'au moins 4,5:1 dans les deux thèmes ; contours d'interface d'au moins 3:1.
- Chaque écran qui traite un fichier montre cinq états : vide, en file d'attente, en cours (avec progression), en erreur, terminé.
- Pas de défilement horizontal à 320 px ; marge latérale de 16 px ; cibles tactiles d'au moins 44 px.
- Les prix et quotas du kit sont provisoires (`[prix]`, `[n]`) : ils viennent de `config/voxia.php`.

**Structure de la page d'accueil** (maquette « B Nocturne », `designs/Voxia Landing - B Nocturne.dc.html`)

1. En-tête sticky : logo, navigation (Usages, Fonctionnement, Tarifs, Questions), langue, « Se connecter ». Menu mobile.
2. Héros : pastille des sources (YouTube, TikTok, fichiers audio et vidéo), titre « Chaque vidéo contient un texte. Voxia te l'écrit. », texte d'appui, formulaire du lien (le plus important de la page).
3. Aperçu du résultat : onde, horodatages, ligne surlignée, pastilles TXT, PDF, SRT, VTT, MP3.
4. Fonctionnement en trois étapes : lien ou fichier, transcription, copie ou téléchargement.
5. Ce que tu récupères : trois livrables (transcription horodatée TXT et PDF ; sous-titres SRT et VTT ; fichier audio MP3).
6. Pour qui : créateurs de contenu, marketeurs, e-commerçants.
7. Tarifs : Gratuit, Pro (recommandé), Pack ; prix provisoires.
8. Questions fréquentes (`<details>`).
9. Appel final « Ta prochaine vidéo, en texte. » et pied de page : CGU, confidentialité, contact.

**Écarts à trancher avant l'intégration** (issus de l'ancienne maquette ; à relire pour la landing Nocturne)

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
