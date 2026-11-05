# Avancement Voxia

Source : `docs/cahier-des-charges.md` (3 novembre 2026).
Mise à jour à la fin de chaque tâche (voir `CLAUDE.md` §6).

## 1. Planning (cahier §9)

- [ ] Étape 1 — Chaîne de traitement : lien, téléchargement, MP3, transcription, en ligne de commande (2 à 3 semaines)
- [ ] Étape 2 — Comptes, historique et affichage de la transcription (F2, F5) (2 semaines) *(Comptes et F5 faits, voir §2 ; F2 partiel : lecture faite, exports à l'étape 3.)*
- [ ] Étape 3 — Exports (F4), lecteur synchronisé et analyse de script (F7) (2 à 3 semaines)
- [ ] Étape 4 — Plans, quotas, paiement et back-office (F6) (2 semaines) *(Quotas, Packs, compteur et paiement faits (Paddle, sandbox), voir §2 F6 et §3 ; le back-office est fait, voir §2 « Back-office » ; à vérifier dans un navigateur, P113.)*
- [ ] Étape 5 — Page d'accueil, page des tarifs, CGU, puis bêta privée avec 20 à 50 créateurs et marketeurs (2 semaines) *(Page d'accueil, tarifs, questions fréquentes, brouillons des CGU et de la confidentialité, contact faits, voir §2 « Pages publiques » ; relecture juridique et bêta restent ouverts, P132.)*

## 2. Critères d'acceptation

### F1 — Transcription depuis un lien ou un fichier

- [ ] Un lien public valide de 10 minutes donne une transcription sans autre action que le lancement. *(Partiel : chaîne complète et écran de lancement testés avec les faux services ; aucun lien réel essayé, yt-dlp absent, voir P16.)*
- [ ] Une vidéo privée, supprimée, bloquée dans la région ou un live en cours affiche un message clair et ne décompte rien. *(Partiel : messages et motifs affichés sur l'écran avant tout lancement, testés ; arrêt avant téléchargement pour le live ; le décompte n'est écrit qu'à la fin réussie, et un refus pour quota ou durée n'écrit rien.)*
- [x] Un échec technique ne décompte rien du quota. *(Testé : échec à chaque étape, fichiers temporaires supprimés, aucune ligne de consommation écrite ; `tests/Feature/Quotas/QuotasTest.php`.)*
- [ ] Un lien déjà transcrit par le même utilisateur renvoie vers la transcription existante. *(Partiel : vérifié pour YouTube et TikTok avec identifiant connu, et l'écran affiche la transcription existante avant lancement (testé). Un lien court `vm.tiktok.com` n'a pas d'identifiant avant résolution, donc pas de contrôle à la création, voir P32.)*

### F2 — Transcription horodatée avec ponctuation propre

*(Partiel : moteur, regroupement en paragraphes et écran de lecture faits et testés. Exports : étape 3. Fluidité du défilement non mesurée, voir ci-dessous.)*

- [ ] Le clic sur un horodatage positionne la lecture à 1 seconde près. *(Partiel : testé côté serveur (début exact en millisecondes, MP3 servi avec requêtes d'intervalle). Vérifié dans le navigateur sur le MP3 : clic sur l'horodatage 1 min 13,9 s, position lue 1,5 s plus tard : 1 min 15,4 s, lecture en cours. Le lecteur YouTube n'a pas pu être vérifié : son API renvoyait une erreur dans l'environnement de test.)*
- [ ] Une transcription de 3 heures s'affiche et reste fluide au défilement. *(Partiel : 703 paragraphes rendus en moins de 2 s (test). Fluidité non mesurée : dans le navigateur de test, même une page au repos n'atteint que 1 à 2 images par seconde, donc la mesure n'a pas de sens ici. À mesurer sur un poste normal.)*

Décisions de l'écran de lecture :
- Paragraphes rendus d'un coup, hors écran ignorés par le navigateur (`content-visibility`), sans virtualisation en JavaScript.
- La copie et le surlignage du texte passent par `textContent` : le texte de la transcription n'est jamais inséré comme HTML.
- Le bandeau « Passe au Pro » s'affiche en fin de texte pour un aperçu ; son lien arrive à l'étape 4.
- Les boutons d'export sont inactifs, avec la mention « bientôt », jusqu'à l'étape 3.

Points ouverts :
- Vérifier le lecteur YouTube sur un poste connecté avec une vraie vidéo, et la copie dans le presse-papiers (le message de confirmation n'est pas apparu dans le navigateur de test).
- Mesurer la fluidité du défilement sur un poste normal avec une transcription de 3 heures.

### F3 — Extraction de l'audio en MP3

- [ ] Le MP3 téléchargé a la même durée que la vidéo source, à 1 seconde près. *(Partiel : `AudioExtractor` testé sur une vidéo et un bip générés, 1 s près, débit 192 kbit/s vérifié ; reste à le brancher sur le téléchargement réel, étape 1.)*
- [x] Le nom du fichier ne contient que des caractères valides sous Windows, macOS et Linux. *(Service `NomFichierAudio` testé sur 10 titres piégés.)*

### F4 — Export TXT, PDF, SRT, VTT

- [ ] Les fichiers SRT et VTT s'importent sans erreur dans YouTube Studio et CapCut. *(Partiel : un validateur contrôle chaque règle sur les cinq cas (`tests/Feature/Exports/SousTitresTest.php`), et des exemples sont prêts dans `storage/app/exports-test/`. L'import réel reste à faire à la main, P78.)*
- [ ] Les accents et caractères non latins s'affichent correctement dans chaque format. *(Partiel : TXT, SRT et VTT sont testés en UTF-8 avec accents et cyrillique ; le texte du PDF a été relu par pdf.js, français, cyrillique et grec. L'arabe et les écritures CJK ne sont pas couverts dans le PDF, P80.)*

Livré avec F4 : TXT et PDF avec ou sans horodatages ; SRT et VTT (42 caractères, 2 lignes, 1 à 7 secondes, sans chevauchement, coupure à la ponctuation puis entre deux mots). Barre d'export de la lecture branchée : les formats du plan sont des liens, les autres sont verrouillés avec le plan qui les débloque. Route `transcriptions.export`, 403 côté serveur pour un format hors plan. Tests : `tests/Feature/Exports`, `LectureTranscriptionTest`.

### F5 — Historique et recherche

- [x] Une recherche sur 500 transcriptions répond en moins d'une seconde. *(Mesuré : 150 à 185 ms pour la page complète, 500 transcriptions de 40 paragraphes chacune, PostgreSQL 18 sur le poste de développement, sans statistiques fraîches. Test `ChargeRechercheTest`. Mesure sur un poste de développement, pas sur le serveur de production, voir P69.)*
- [x] Une transcription supprimée disparaît de la liste et de la recherche. *(SuppressionTranscriptionTest, dont le test de recherche sur PostgreSQL.)*

Livré avec F5 : liste (miniature, titre, plateforme, durée, date, statut), tri par date, pagination, recherche plein texte réservée au Pro et au Pack (extrait surligné, lien vers l'horodatage ouvert dans le lecteur), suppression avec confirmation et MP3, conservation du plan Gratuit (purge à 30 jours, rappel à 3 jours), purge des MP3 et des fichiers importés. Tests : `tests/Feature/Historique`.

### F6 — Offre gratuite limitée

- [ ] Aucun critère d'acceptation défini dans le cahier. Voir point ouvert P0. *(Règles de quota codées et testées : 20 tests dans `tests/Feature/Quotas`, dont la 6e vidéo du mois, le changement de mois, la date anniversaire, l'expiration d'un Pack et deux lancements simultanés. Paiement : 28 tests dans `tests/Feature/Paiement`, un par cas de webhook, plus le même webhook reçu deux fois. Back-office : voir §2 « Back-office ».)*
- Paiement (cahier §5, « Paiement ») — critères couverts par des tests, à valider à la main dans le sandbox Paddle (P102) :
  - [x] Prix en dollars US lus dans la config (`voxia.plans.*` pour l'affichage, `voxia.paiement.prix` pour les identifiants Paddle).
  - [x] Webhooks vérifiés (signature) et idempotents : abonnement créé, renouvelé, modifié, résilié, paiement échoué, achat du Pack. Un même webhook reçu deux fois ne produit qu'un effet.
  - [x] Après paiement : plan mis à jour, quotas recalculés au nouveau plan.
  - [x] Aperçu partiel : relancé en version complète après paiement, sans recompter le Gratuit.
  - [x] Espace Abonnement : formule, passage mensuel ↔ annuel, résiliation (accès jusqu'à la fin de la période), annulation de la résiliation, factures, Packs et leur expiration.
  - [x] Recalcul quotidien des plans (`voxia:recalculer-plans`) : un Pack expiré ramène au plan restant.
  - [ ] Test de bout en bout avec le sandbox Paddle réel (aucune clé ni webhook réel dans cet environnement).
  - [ ] TVA et factures : gérées par Paddle (texte affiché), pas vérifiées sur une facture réelle.
- Fenêtres d'upgrade (cahier §2, parcours d'upgrade) : une fenêtre superposée par déclencheur, l'écran d'origine reste en place. Tests : `tests/Feature/Upgrade/DeclencheursUpgradeTest.php`. Vérifiées par les tests, pas encore dans un navigateur (P109).
  - [x] 6e vidéo du mois en Gratuit : compteur et plans Pro et Pack côte à côte ; le lien refusé est gardé et revient dans le champ après paiement.
  - [x] Aperçu de 10 minutes : bandeau « Passe au Pro pour la transcription complète » avec les minutes non transcrites ; l'aperçu est relancé après paiement.
  - [x] Export verrouillé (PDF, VTT, et PDF de l'analyse) : le format verrouillé ouvre la fenêtre.
  - [x] Recherche dans l'historique en Gratuit : la fenêtre s'ouvre dès qu'un terme est saisi.
  - [x] Analyse de script sans crédits IA (Gratuit, Pack) : la fenêtre s'ouvre ; un Pro à court de crédits n'y est pas renvoyé.
  - [x] Retour après paiement : `paiement?retour=` n'accepte qu'un chemin de l'application (`App\Support\CheminLocal`).
  - [ ] Parcours réel : payer dans le sandbox Paddle puis revenir sur l'écran d'origine (P108).

### F7 — Analyse de la structure d'un script publicitaire

- [ ] Sur 20 pubs découpées à la main, les limites des parties tombent à 2 secondes près dans 80 % des cas (cible à valider). *(Non mesuré : la commande `voxia:eval-scripts` est prête, mais les 20 pubs de référence ne sont pas encore fournies, P86.)*
- [x] Les parties ne se chevauchent pas et suivent l'ordre de la vidéo. *(ValidateurAnalyseTest, AnalyseScriptTest.)*
- [ ] L'analyse d'une pub de 60 secondes s'affiche en moins de 15 secondes. *(Non mesuré avec l'API réelle, P85.)*
- [ ] Un clic sur une partie lance la lecture à son début. *(Partiel : le test vérifie que le début exact en millisecondes part vers le lecteur (AffichageAnalyseTest) ; la lecture réelle dans un navigateur n'est pas vérifiée, P87.)*

Livré avec F7 (tests dans `tests/Feature/Analyse`, faux analyseur `FakeScriptAnalyzer`, aucun appel réel) : contrat `App\Contracts\ScriptAnalyzer` et adaptateur `ClaudeScriptAnalyzer` (API Messages, modèle `claude-haiku-5-5`, sorties structurées par `output_config.format`), validation serveur `ValidateurAnalyse`, relance unique dans `ServiceAnalyseScript`, tâche `AnalyserScript` (file `high` pour le Pro), bouton et statut dans `AnalyseScript` (Livewire), barre et parties reprises de la maquette, exports TXT et PDF (`ExporteurAnalyse`, route signée), pastille dans l'historique et la recherche, décompte d'un crédit IA dans `QuotaService`, coût dans `processing_logs`, commande `voxia:eval-scripts`.

Décisions F7 :
- Le modèle ne renvoie ni texte ni horodatage : il donne les numéros du premier et du dernier mot de chaque partie. Les bornes et le texte viennent des mots transcrits.
- Une réponse invalide (refus, troncature, indices hors du texte, chevauchement) et un échec d'appel sont relancés une fois. Un échec final ne décompte rien, mais son coût est journalisé.
- Une analyse terminée n'est jamais relancée (un second crédit serait décompté).
- Le crédit IA n'est pas bloquant tant que l'étape 4 n'est pas branchée (`QuotaService::verifierCreditIa`).
- Limite de durée : 180 s, vérifiée côté serveur. Une limite de recalage de 2 s entre une borne renvoyée et le mot le plus proche rend la réponse invalide.
- `phpunit.xml` passe `memory_limit` à 512 Mo : la suite complète dépasse 128 Mo avec les rendus PDF (P91).

### Back-office (cahier §5, « Back-office d'administration »)

*Filament 4.16.1 sur `/admin`, réservé aux comptes `is_admin` (commande `voxia:make-admin {email}`). Tests : `tests/Feature/Administration` (35 tests). Vérifié par les tests, pas encore dans un navigateur (P113).*

- [x] Liste des utilisateurs : plan, date d'inscription, consommation du mois (vidéos, heures, crédits), Packs actifs, filtre par plan. *(ListesBackOfficeTest, AjustementQuotaTest)*
- [x] Liste des traitements : statut, plateforme, motif d'échec, durée de chaque étape (onglet « Étapes »), coût estimé ; filtres par statut et par période. *(ListesBackOfficeTest)*
- [x] Ajustement d'un quota avec motif obligatoire, journalisé. *(AjustementQuotaTest)*
- [x] Remboursement d'un traitement raté par une ligne de consommation inverse, journalisé, jamais deux fois. *(RemboursementTest)*
- [x] Tableau de bord : traitements du jour, taux d'échec du téléchargement sur 24 h, coût moyen par heure de vidéo (30 jours), inscriptions, conversions Gratuit vers payant. *(IndicateursTest)*
- [x] Alerte email aux administrateurs quand le taux d'échec du téléchargement dépasse 5 % sur la dernière heure (seuil dans la config). Commande `voxia:alerter-echecs`, planifiée toutes les cinq minutes. *(AlerteEchecsTest)*
- [x] Un non-administrateur reçoit une erreur 403. *(AccesBackOfficeTest)*
- [x] Les deux actions (ajustement, remboursement) sont inscrites au journal `journal_administration`, consultable dans `/admin`. *(AjustementQuotaTest, RemboursementTest)*

Décisions du back-office :
- Filament 5 (dernière version, 5.11.1) exige Livewire 4, alors que le projet reste sur Livewire 3 (CLAUDE.md §3). Installé : Filament 4.16.1, dernière version 4.x compatible avec Livewire 3 (P112).
- Accès : `User::canAccessPanel()` renvoie `is_admin`. Un visiteur est renvoyé vers `/admin/login` ; un compte connecté sans droit reçoit un 403 (middleware de Filament).
- Journal : une ligne par action, motif, administrateur, cible et détails. Les lignes ne se modifient jamais et l'écran est en lecture seule.
- Ajustement : une ligne de consommation négative (motif `ajustement_admin`), donc comptée comme une vraie consommation. Compteurs : vidéos (Gratuit), heures du mois (Pro), heures du plus ancien Pack, crédits IA du mois. Les heures se saisissent en heures. La limite affichée inclut l'ajout accordé.
- Remboursement : seulement sur un traitement en échec ; une ligne inverse par ligne de consommation active (`UsageRecord::reverse`), dans une seule transaction. Motif obligatoire, en plus du journal (choix, non demandé par le cahier).
- Alerte : pas de renvoi avant le délai de 60 minutes (`voxia.administration.alerte_echecs.delai_minutes`, choix non fixé par le cahier). Pas d'alerte sans tentative sur la fenêtre.
- Conversions : comptes dont le premier achat (abonnement Paddle ou Pack) tombe dans les 30 derniers jours.
- Tableau de bord : widgets non différés, pour que les chiffres sortent avec la page.
- Langue : celle du compte administrateur (`DefinirLangue`), français par défaut.

### Comptes (cahier §2 parcours étapes 1 et 2, §5 Comptes)

*Le cahier ne numérote pas ces règles : chacune a son test dans `tests/Feature/Comptes/`. Suite complète : 228 tests passent, 4 ignorés comme avant.*

- [x] Inscription par email et mot de passe, lien de vérification envoyé. *(InscriptionTest)*
- [x] Vérification de l'email exigée avant la première transcription (lancement Livewire et envoi de fichier). *(VerificationEmailTest, VerificationLancementTest)*
- [x] Connexion, mot de passe oublié, nouveau mot de passe par lien. *(ConnexionTest)*
- [x] Lien collé par un visiteur conservé pendant l'inscription et la vérification, puis repris à l'arrivée dans l'application. *(LienEnAttenteTest, dont la conservation à la connexion et la non-relance)*
- [x] Connexion Google (Socialite), désactivée par défaut par `VOXIA_GOOGLE_LOGIN`. *(ConnexionGoogleTest)* *(Partiel : Socialite simulé, aucun essai avec un vrai client Google, voir P52.)*
- [x] Limite de comptes gratuits par IP, avec message neutre, aussi pour Google. *(InscriptionTest, ConnexionGoogleTest)* *(Limite fixée à 2 par IP, voir P47.)*
- [x] Réglages : nom, email, mot de passe, langue de l'interface. *(ReglagesTest)*
- [x] Suppression du compte et de toutes ses données (transcriptions, MP3, fichiers) après confirmation. *(ReglagesTest : fichiers et lignes absents, voisins intacts)*
- [x] Emails transactionnels au style Signal ; en local, journal (`MAIL_MAILER=log`). *(EmailsTransactionnelsTest)* *(Partiel : Mailpit non essayé, voir P55.)*
- [x] Limitation des tentatives : connexion 5 par minute, inscription 10 par heure et par IP, réinitialisation 5 par heure. *(ConnexionTest, InscriptionTest)*
- [x] Écrans au style Signal (`layouts/marketing`, `layouts/app`), accueil avec le champ de lien. *(EcransComptesTest : rendu, sans ressource externe)*
- [x] Page Compte au style et à la structure de la maquette « Compte - Nocturne » : navigation latérale avec section active (suivi au défilement), formule en pastille et trois cartes de quota selon le plan, profil et mot de passe en grille, langue, factures, données, zone de suppression teintée. *(ReglagesTest, EcransComptesTest. Vérifié par les tests, pas encore dans un navigateur.)*
  - Décision : les cartes « Exports » (Gratuit, Pack) lisent la config (`voxia.plans.*.exports`) ; la maquette affiche « MP3 » pour le Gratuit, qui n'y figure pas. À confirmer.

### Page d'accueil (cahier §7, maquette Signal)

*Tests : `tests/Feature/Accueil/PageAccueilTest.php` (un test par critère de la liste de corrections), plus `CharteSignalTest` pour les animations. Mise en page vérifiée dans Chrome : aucun débordement horizontal à 375, 768 et 1440 px, sur l'accueil et sur une page du pied.*

- [x] Structure et animations de la maquette reprises en composants Blade (`components/accueil/`) sur `layouts/marketing` : en-tête, héros, aperçu, bandeau, usages, fonctionnement, fiche technique, tarifs, questions, pied. *(PageAccueilTest)* *(Comparaison visuelle au trait près : à faire par le client.)*
- [x] Carte Créateurs : « Une transcription, quatre formats. », formats réels (.txt, .pdf, .srt et .vtt, .mp3). *(PageAccueilTest)*
- [x] Carte Marketeurs gardée telle quelle (F7 dans le MVP). *(PageAccueilTest)*
- [x] Prix en dollars US lus dans `config/voxia.php` : Gratuit 0 $, Pro 12 $ par mois ou 96 $ par an, Pack 5 $. Bascule mensuel / annuel sur le Pro. *(PageAccueilTest)*
- [x] Quotas et contenus du Gratuit, du Pro et du Pack lus dans la config (vidéos par mois, durée maximale, heures, validité). *(PageAccueilTest)*
- [x] MP3 présenté comme inclus dans tous les plans. *(PageAccueilTest)*
- [x] Fiche technique et texte sous le champ : MP3, M4A, WAV, MP4, MOV, WEBM (`voxia.uploads.extensions`). *(PageAccueilTest)*
- [x] Champ du héros : visiteur envoyé à l'inscription, lien conservé ; connecté renvoyé vers le tableau de bord, lien en attente. *(PageAccueilTest pour le formulaire ; LienEnAttenteTest pour le parcours)*
- [x] En-tête et pied branchés : Usages, Fonctionnement, Tarifs, Se connecter, Essayer gratuitement, Confidentialité, Conditions, Contact. Les pages du pied existent (voir « Pages publiques »). *(PageAccueilTest)*
- [x] Animations (onde, bandeau, frappe, paquet, curseur) coupées sous `prefers-reduced-motion`. *(CharteSignalTest, PageAccueilTest : contrôle du CSS ; pas d'essai avec le réglage du système activé.)*
- [x] Aucune barre de défilement horizontale à 375, 768 et 1440 px. *(Mesure dans Chrome, sur l'accueil et une page du pied.)*
- [x] Titre, description, balises Open Graph et Twitter, image de partage 1200 × 630 au style Signal (`public/images/partage-voxia.png`, générée avec GD). *(PageAccueilTest)*
- [ ] Langues reconnues et durée de conservation : marquées « [À CONFIRMER] » (P121, P122).
- [ ] Textes juridiques et contact : brouillons et formulaire faits ; relecture juridique et adresse de contact à fournir (P132).

**Décisions de la page d'accueil**

- Les écarts de la section 7 du cahier sont tranchés comme dans la liste de corrections : carte Créateurs reformulée, devise en dollars, Pack ajouté, MP3 inclus partout, formats d'upload alignés sur la configuration.
- Polices : la maquette charge Geist, Geist Mono et Instrument Serif depuis Google Fonts. Le projet les héberge déjà par fontsource (`resources/js/app.js`) : pas d'appel à un CDN, conforme à la charte.
- Les boutons de l'aperçu produit (Copier, .txt, .srt, .vtt, .mp3) sont décoratifs : ils sont masqués aux lecteurs d'écran et sortent du parcours clavier.
- Les textes des sections et des tarifs sont dans `lang/fr/accueil.php` et `lang/en/accueil.php`. Les montants, quotas et formats sont passés en remplacements : rien n'est écrit en dur.
- Le pied de page pointe vers les pages publiques (tarifs, questions, confidentialité, conditions, contact) : voir « Pages publiques ».

### Pages publiques et données du compte (cahier §5, §7, §9 étape 5)

*Tests : `tests/Feature/Pages/` (`PagesPubliquesTest`, `ContactTest`, `ExportDonneesTest`), un test par critère. Textes : `lang/fr/pages.php` et `lang/en/pages.php`. Mise en page à vérifier dans un navigateur (P136).*

- [x] Tarifs : comparatif détaillé (Gratuit, Pro, Pack) et FAQ tarifaire ; prix, quotas, durées et exports lus dans la config. *(PagesPubliquesTest)*
- [x] Questions fréquentes : transcription, comptes et plans, données ; formats et langues lus dans la config. *(PagesPubliquesTest)*
- [x] Conditions et confidentialité : brouillons en français et en anglais, bandeau « BROUILLON — à faire relire par un juriste avant lancement » en tête. *(PagesPubliquesTest)* Relecture par un juriste : P132.
- [x] Conditions : l'utilisateur garantit avoir les droits sur les vidéos qu'il transcrit ; aucune transcription n'est publique ; durées de conservation depuis la config. *(PagesPubliquesTest)*
- [x] Confidentialité : sous-traitants (ElevenLabs, Anthropic, prestataire de paiement, hébergeur, stockage, emails, proxys de téléchargement), transferts hors Union européenne, droits d'accès, de rectification, d'effacement, de portabilité (export ZIP) et réclamation à la CNIL. *(PagesPubliquesTest)*
- [x] Éditeur : EVANOE, SIRET 919 260 000 00014, 13 rue de la Tuilerie, Betouille, 77950 Rubelles (`voxia.editeur`). Forme juridique et email de contact : espaces laissés à compléter. *(PagesPubliquesTest)*
- [x] Aucun bandeau de consentement : aucun outil de mesure d'audience, aucun cookie publicitaire (voir Décisions). *(PagesPubliquesTest)*
- [x] Formulaire de contact protégé contre le spam : champ piège, délai minimal avant envoi, limite par adresse IP (`voxia.contact`). *(ContactTest)*
- [x] Contact : le message part en file vers `VOXIA_CONTACT_EMAIL`, avec le visiteur pour réponse ; sans adresse configurée, le formulaire n'est pas affiché. *(ContactTest)*
- [x] Export de toutes les données en ZIP depuis les réglages (« Vos données ») : compte, transcriptions en JSON et en texte horodaté, MP3, analyses de script, consommation. Préparé par la file, lien signé reçu par email. *(ExportDonneesTest)*
- [x] Export réservé au propriétaire (un autre compte reçoit 404, une adresse sans signature 403) ; retiré avec le compte ; purge après 24 h (`voxia:purger-exports`, planifiée). *(ExportDonneesTest)*
- [x] Pages dans les deux langues sans clé de traduction visible. *(PagesPubliquesTest, ContactTest)*

Décisions des pages publiques :
- Consentement : aucun outil de mesure d'audience ni publicité ; seuls les cookies nécessaires (session, protection des formulaires, choix de langue) sont posés. Donc aucun bandeau. Si un outil de mesure est ajouté, la confidentialité et le bandeau sont à mettre à jour avant (une mesure exemptée de consentement par la CNIL reste à vérifier à ce moment-là).
- Textes juridiques : tutoiement, comme le reste de l'interface (CLAUDE.md §5). Aucune valeur inventée : le plafond de responsabilité, le droit applicable, l'hébergeur, le stockage, l'envoi d'emails et les proxys restent entre crochets.
- Prestataire de paiement nommé « Paddle », comme dans le code (`voxia.paiement.fournisseur`). Le cahier §8 le donne encore « à décider » (P132).
- Export : préparé par la file (`ExporterDonneesCompte`), fichier `exports/{id}/voxia-donnees.zip` sur le disque de stockage, lien signé de 24 h dans l'email. Le journal technique de traitement (coûts internes) n'y figure pas : c'est une donnée d'exploitation (P134).
- Contact : aucun CAPTCHA, car aucun service de vérification n'est dans la stack. Les messages ne sont pas enregistrés en base : ils partent seulement par email (P133).
- Pied de page : liens Tarifs et Questions fréquentes ajoutés aux liens existants.

### Langues (CLAUDE.md §1 : français et anglais)

*Tests : `tests/Feature/Langue/LangueTest.php` (un test par critère), plus les tests de pages existants, qui rendent les écrans en français.*

- [x] Tous les textes visibles des pages publiques, de l'application, des courriels, des PDF et des écrans d'upgrade passent par `lang/fr` et `lang/en`. Les clés sont les mêmes dans les deux langues, sans valeur vide. *(LangueTest : parité et valeurs vides)*
- [x] Chaque page principale s'affiche dans les deux langues sans clé de traduction visible : accueil, confidentialité, conditions, contact, connexion, inscription, tableau de bord, historique, réglages, abonnement. *(LangueTest ; le détecteur est lui-même testé)*
- [x] Ordre de choix : adresse `/fr` ou `/en`, puis préférence du compte, puis choix mémorisé (cookie `voxia_langue`), puis langue du navigateur (`Accept-Language`), puis anglais. *(LangueTest)*
- [x] Pages publiques sous `/fr` et `/en` ; `/` redirige vers la langue du visiteur ; balises `hreflang` (fr, en, x-default). *(LangueTest)*
- [x] Sélecteur dans l'en-tête (pages publiques et application) et dans les réglages (« Suivre le navigateur », Français, English). Le choix est enregistré sur le compte s'il y en a un. *(LangueTest)*
- [x] Dates, nombres et devises selon la langue (`App\Support\Formats`, `App\Support\Montant`). *(LangueTest)*
- [x] Courriels envoyés dans la langue du destinataire (suppression, rappel, transcription prête, alerte administrateur). *(LangueTest, EmailsTransactionnelsTest)*
- [ ] Back-office Filament : libellés encore en français (P126).

Décisions de la langue :
- Adresses `/fr` et `/en` seulement pour les pages publiques (accueil, confidentialité, conditions, contact). Les écrans de l'application gardent leurs adresses ; leur langue vient du compte ou du navigateur.
- Le cookie `voxia_langue` (un an) est posé par une adresse `/fr` ou `/en` et par le sélecteur. Il sert aux visiteurs sans compte, et aux requêtes Livewire qui n'ont pas l'adresse de la page.
- `App\Support\Langue::resoudre` : les comptes ont une préférence à partir de l'inscription, car la langue courante y est enregistrée (`CreateNewUser`, connexion Google). « Suivre le navigateur » efface la préférence pour un compte existant.
- `users.locale` devient facultatif (migration `2026_11_04_300000`). Les comptes existants gardent leur valeur : celle posée par défaut à la création de la colonne (`fr`), qui n'était pas un choix de l'utilisateur.
- Le sélecteur envoie son choix à `POST /langue`, qui renvoie sur la même page dans l'autre langue (`/fr/contact` vers `/en/contact`). Un retour externe n'est jamais suivi (`CheminLocal`).
- Les comptes de test sont créés en français (fabrique), comme avant ; les tests de langue fixent leur langue.
- Héros en anglais : « Every video has words in it. » / « Voxia writes them down. » (variante A, choix à confirmer, P130).

## 3. Décisions

- Comptes : Fortify pour l'authentification (vues Signal dans `resources/views/auth`), sans double authentification ni passkeys (hors MVP, désactivés dans `config/fortify.php`). Socialite pour Google.
- Lien en attente : colonne `users.lien_en_attente`. Tant que le visiteur est anonyme, le lien est gardé en session (`App\Services\Comptes\LienEnAttente`) ; il est reporté sur le compte à la connexion ou à l'inscription (événement `Login`).
- Lancement : à l'arrivée dans l'application (`/tableau-de-bord`), une fois l'email vérifié. Le bouton « Transcrire » est déjà la demande de lancement, donc la reprise ne demande pas de second clic.
- Lancement depuis le web (`POST /transcriptions`) : seules les adresses `http(s)` sont acceptées. `LancerTranscription` sait aussi lire un fichier local : ce chemin ne doit jamais venir d'un formulaire.
- Limite par IP : compte les comptes au plan Gratuit créés depuis l'IP (`users.inscription_ip`). Les comptes Pro ne comptent pas. Le message est volontairement neutre.
- Téléchargements (MP3 et exports) : routes `transcriptions.mp3` et `transcriptions.export` protégées par le middleware `signed`, liens émis par la page avec `URL::temporarySignedRoute`. Durée : `voxia.telechargement.duree_lien_minutes` (15 minutes). Le propriétaire est vérifié en plus : un autre compte reçoit 404, un lien modifié ou expiré 403, une adresse sans signature 403. Un MP3 supprimé par la conservation (`mp3_chemin` vide) s'affiche comme « MP3 supprimé » avec la durée de conservation ; un fichier absent du disque répond 404.
- Fichiers importés : stockés dans `voxia.uploads.dossier`, jamais exposés par une route. Rien à signer tant qu'aucun téléchargement ne les sert.
- Lecteur audio (`transcriptions.audio`) : reste authentifié, sans lien signé (voir P84).
- Erreur de connexion Google : clé `google` (et non `email`), car le bloc Google de la page de connexion lit cette clé. Avant, le message était envoyé sous `email` et n'apparaissait pas.
- Tests : `phpunit.xml` fixe `VOXIA_GOOGLE_LOGIN=false`, pour que le bouton Google n'apparaisse dans aucun rendu de test par défaut.
- Connexion Google : aucun rattachement à un compte non vérifié (sinon un tiers pourrait prendre le compte). Un compte Google n'a pas de mot de passe ; la réinitialisation en crée un.
- Suppression : confirmation par la saisie de l'adresse email (valable aussi pour Google). Les lignes partent dans une transaction ; les fichiers MP3 par la tâche `SupprimerFichiersTranscriptions`. Déconnexion avant suppression : `Auth::logout()` enregistre le compte pour changer son jeton, ce qui réinsérait la ligne supprimée (bug trouvé par les tests).
- Langue : middleware `DefinirLangue` (langue du compte, sinon défaut de l'application). Fichiers `lang/fr/validation.php`, `passwords.php`, `auth.php` ajoutés, car le framework ne fournit que l'anglais.
- Emails : thème Markdown `signal` (`resources/views/vendor/mail/html/themes/signal.css`), sans police externe.
- Environnement Windows : `composer require` échoue sans `--ignore-platform-req=ext-pcntl --ignore-platform-req=ext-posix` (Horizon, P38). Le lock n'a fait qu'ajouter des paquets, aucune version existante n'a bougé.

- Écran « Nouvelle transcription » (F1) : composant Livewire `App\Livewire\NouvelleTranscription` dans `resources/views/app/tableau-de-bord.blade.php`, sur la mise en page `app`. Le formulaire POST `/transcriptions` est remplacé par ce composant ; la page de statut est `/transcriptions/{id}`.
  - Aperçu d'un lien : les métadonnées sont lues par la file (`RecupererApercu`), jamais par une requête web. L'état vit en cache 15 minutes (`voxia.apercu.cache_secondes`) et l'écran l'interroge toutes les 1,5 s tant qu'il est « en cours ». Les liens invalides sont refusés localement, sans aucune lecture.
  - Lancement refusé, avant tout décompte, pour : aperçu non prêt, motif d'erreur de l'aperçu (privée, supprimée, région, live, échec technique), durée au-delà d'un plan payant (`trop_longue`). Le lancement exige un email vérifié, comme l'ancienne route.
  - Langue : liste `voxia.langues_forcables` (fr, en, es, de, it, pt), défaut « détection automatique ». Liste validée avec ElevenLabs Scribe v2 (P61).
- Gratuit et vidéo de plus de 10 minutes : pas de refus. La chaîne ne transcrit que les 10 premières minutes (`voxia.plans.free.max_video_seconds`) : l'extraction du MP3 est coupée à cette durée (`-t`), la transcription est marquée `est_apercu`, et l'écran comme la page de statut affichent « Passe au Pro pour la transcription complète ». La durée complète reste enregistrée (`duree_secondes`), donc l'e-mail des vidéos longues s'applique aussi. Les plans payants gardent le refus `trop_longue`.
  - Fichier importé : la durée source vient de ffprobe (`ExtraitAudio::dureeSourceSecondes`) ; elle est enregistrée avant le contrôle de durée, ce qui n'était pas fait pour les fichiers.
  - Pas encore : un passage au Pro ne relance pas automatiquement la transcription complète (F6, paiement).
- Envoi d'un fichier de 2 Go : **envoi découpé**, pas envoi direct. Raisons : Livewire 3.8 n'envoie pas par morceaux et sa limite par défaut est de 12 Mo ; PHP est en 2 Mo par défaut ici, donc un envoi d'un seul bloc de 2 Go imposait de modifier `php.ini` sur chaque poste ; un envoi par morceaux reprend après une coupure (relance du fichier depuis le début, voir P63). Un envoi direct vers un stockage objet (URL signées) restera la bonne voie en production avec S3 : à faire quand le disque S3 est configuré.
  - Morceaux de 1 Mo (`voxia.uploads.morceau_octets`), route `POST /televersements/morceaux` (email vérifié, `Televersement`). Le dernier morceau réassemble le fichier sous un nom nettoyé (`NomFichierAudio`), contrôle sa taille, puis supprime les morceaux. Le navigateur (`resources/js/televersement.js`) affiche la progression et ne transmet à Livewire que le nom final.
  - Contrôles avant écriture : extension (`voxia.uploads.extensions`), taille annoncée (`voxia.uploads.max_size_mb`, 2048), nombre de morceaux cohérent, identifiant d'envoi au format UUID (sinon un chemin pourrait sortir du dossier).
  - Un fichier reçu est relu par son nom pour le compte connecté seulement : `Televersement::cheminFichier`.
- `QuotaService` : vérifié par `LancerTranscription` avant chaque création (lien ou fichier), puis par `ChaineTranscription` au traitement, avec la durée réelle. Le décompte est écrit dans la transaction qui passe la transcription à « Terminé » (F6, voir §3).
- E-mail « vidéo prête » (F1) : écouteur `PrevenirFinVideoLongue` sur `TranscriptionCompleted`, en file, seuil `voxia.notifications.long_video_email_after_seconds` (900 s). Mailable `TranscriptionPrete`, style Signal, textes dans `courriels.transcription_prete`.
- Tests : 37 tests dans `tests/Feature/Transcriptions` (écran, envoi, e-mail, page de statut, lancement sans email vérifié). `VerificationEmailTest` adapté au composant : mêmes attentes, le lancement passe maintenant par Livewire. Suite complète : 262 tests passent, 4 ignorés comme avant.
- F5 — Historique et recherche (`tests/Feature/Historique`) :
  - Recherche par langue : chaque paragraphe garde `config_recherche`, copie de la langue de sa transcription (`App\Support\ConfigurationRecherche`, liste dans `voxia.recherche.configurations` : fr, en, es, de, it, pt ; le reste utilise `simple`). Un index GIN partiel existe par configuration (`transcription_paragraphs_recherche_french`, etc.), migration `2026_11_03_240000`. Un terme est donc racinisé selon la langue du passage (« école » trouve « écoles » en français).
  - Requête en deux temps : les paragraphes trouvés sont isolés dans un CTE `MATERIALIZED` (recherche d'index en premier), puis joints aux transcriptions du compte, triés par date. L'extrait (`ts_headline`) n'est calculé que pour la page affichée. Sans le CTE, PostgreSQL pouvait choisir une jointure par transcription de 1,2 s juste après un import massif (statistiques absentes) ; avec le CTE, 0,15 s, sans `ANALYZE`.
  - Extrait : le texte est échappé, puis seuls les marqueurs de surlignage deviennent `<mark>`. `ts_headline` écarte certaines balises : l'extrait peut donc différer de la transcription brute (P74).
  - Droits : service `App\Services\Plans\PlanFeatures`. Tant que `VOXIA_PLAN_REEL` est faux (défaut), tout le monde est Gratuit et `users.plan` est ignoré. Pro et Pack ont la recherche, l'historique illimité ; Gratuit a 30 jours et le rappel à 3 jours (`voxia.plans`). L'étape 4 remplacera le drapeau.
  - Suppression : page de confirmation (GET), puis `DELETE`. Les lignes partent dans une transaction ; le MP3 et son dossier partent par `SupprimerFichiersTranscriptions` (file). Un autre compte reçoit 404.
  - Conservation (`voxia:purger-historique`, `voxia:rappeler-suppression`, `voxia:purger-medias`, planifiées dans `routes/console.php`) : purge des transcriptions Gratuit de plus de 30 jours ; un email par compte, une seule fois par transcription (`date_rappel_suppression`), 3 jours avant ; MP3 (`mp3_chemin` remis à null) et fichiers importés de plus de `voxia.retention.media_days`, par date de modification, pour tous les plans. Dossiers vides et morceaux incomplets compris (P59).
  - Lien depuis la recherche : `?t=` en millisecondes, borné à la durée. Le lecteur se place sur ce moment sans lancer la lecture (MP3 après `loadedmetadata`, YouTube à `onReady`).
  - Nouvelles clés de config : `voxia.recherche`, `voxia.conservation`, `voxia.droits`, `voxia.historique.par_page` (20).
- Bug trouvé par l'essai réel sur serveur, et corrigé : les champs de formulaire arrivent en chaînes, la comparaison stricte du nombre de morceaux refusait donc tout envoi réel. Les tests envoient désormais des champs en chaînes, comme le navigateur.
- Exports (F4) : une interface `App\Services\Exports\Exporteur`, une classe par format (`ExporteurTxt`, `ExporteurPdf`, `ExporteurSrt`, `ExporteurVtt`), choisies par `Exporteurs::pour()`. Les exporteurs reçoivent un `App\Data\ContenuTranscription`, sans Eloquent.
- Sous-titres (`GenerateurSousTitres`) : les mots se fusionnent à leur ponctuation pour former des unités qui ne se coupent jamais. Une coupure se fait à la fin de phrase si le sous-titre dure au moins 1 s ; à une virgule seulement si le sous-titre occupe au moins 75 % de la place (`voxia.subtitles.coupure_ponctuation_faible_ratio`) ; sinon à la limite de place. Un sous-titre de moins d'une seconde est fusionné avec le suivant si la durée et les lignes le permettent, sinon il s'étire vers l'avant sans toucher au suivant. Un étirement impossible laisse un sous-titre de moins d'une seconde : le validateur le signalerait.
- PDF : mPDF (`mpdf/mpdf`). Installé avec `--ignore-platform-req=ext-pcntl --ignore-platform-req=ext-posix`, car ces extensions Linux manquent sous Windows ; à rejouer tel quel sur un poste sans elles. Polices dans `resources/fonts` (voir son README).
- Téléchargement : `GET /transcriptions/{id}/exports/{format}?horodatages=0|1`, propriétaire seulement. Les SRT et VTT ignorent le paramètre et gardent leurs horodatages.
- Droits : `PlanFeatures::formatsExport()`, `peutExporter()`, `planMinimalPour()`. Le plan débloquant est le moins cher et vendable (Pro pour PDF et VTT).
- Exports produits pendant la requête web, pas par la file : contraire à la règle de CLAUDE.md §5. Choix assumé pour cette étape (TXT, SRT et VTT sont des mises en forme de texte) ; le PDF est à surveiller, voir P79.
- Exemples à importer : `storage/app/exports-test/` (`exemple.srt`, `exemple.vtt`, `exemple.pdf`, `exemple-avec-horodatages.txt`, `exemple-sans-horodatages.txt`). Générés depuis la fixture ElevenLabs `francais_redige.json`, qui est écrite à la main et non enregistrée (P22). Le dossier `storage/app` est ignoré par git : les fichiers ne sont pas committés.
- Nouvelles clés de config : `voxia.subtitles.coupure_ponctuation_faible_ratio`, `voxia.exports.pdf.marge_mm`.

Décisions des fenêtres d'upgrade :
- Un seul composant `x-upgrade.fenetre` (au-dessus de `x-fenetre-superposee`). Les cartes de plan viennent de `App\Services\Plans\PresentationTarifs` : prix et volumes lus dans `config/voxia.php`, phrases dans `lang/fr/upgrade.php` et `lang/en/upgrade.php`.
- Le paiement reste sur la page `paiement` (overlay Paddle). Après paiement, Paddle renvoie à l'écran d'origine (`returnTo`) au lieu de l'espace Abonnement. Seul un chemin de l'application est accepté.
- Reprise : seul le lien ou le fichier refusé pour la 6e vidéo est gardé en session (30 minutes) et revient dans le champ. Il n'est pas relancé tout seul : le plan peut changer quelques secondes après le paiement, et le lancement reste un geste de l'utilisateur.
- La fenêtre de la 6e vidéo n'est ouverte qu'au plan Gratuit. Un Pro ou un Pack à court de quota reçoit seulement le message d'erreur. Même règle pour l'analyse : seuls les plans sans crédits IA reçoivent la fenêtre.
- Un format verrouillé est un bouton actif (un bouton désactivé ne peut pas ouvrir de fenêtre). Le lien de paiement de l'aperçu garde l'identifiant de la transcription, sinon le webhook ne la relance pas.
- Tests adaptés : le bandeau d'aperçu mène désormais à la fenêtre (`PaiementTest`), et le test de quota lit le compteur mocké (`NouvelleTranscriptionTest`, helper `mockQuotaCompteur`).
- Langues : les textes de l'interface et des courriels sont bilingues (voir « Langues » en §2). Tests existants adaptés à la nouvelle adresse de l'accueil (`/fr`) et à la langue explicite des courriels : `ExampleTest`, `PageAccueilTest`, `EcransComptesTest`, `CharteSignalTest`, `EmailsTransactionnelsTest`, `HistoriqueListeTest`, `ConservationTest`.

### Refonte visuelle « Nocturne » (cahier §7, CLAUDE.md §4)

- [x] Direction « Nocturne » retenue. Elle remplace la charte précédente dans le cahier (§7), dans `CLAUDE.md` (§4) et dans le code. Le handoff est versionné dans `docs/design_handoff_voxia_nocturne/`.
- [x] Tokens dans `resources/css/voxia-tokens.css` : sombre par défaut, clair sous `data-theme="light"`, repli sur `prefers-color-scheme: light`. `app.css` les branche en `@theme inline` ; les classes existantes (`bg-fond`, `text-texte`, `border-filet`, `rounded-bouton`…) restent valides.
- [x] Polices auto-hébergées (fontsource) : Onest (variable), Unbounded 600 (titres), JetBrains Mono 400 et 500 (données). Roboto et Bricolage Grotesque retirés de `package.json`.
- [x] Emails : thème `nocturne.css` (sombre), `config/mail.php` mis à jour. Le fichier `docs/Voxia Direction 3 - Surligneur.dc.html` est supprimé.
- [x] Tests : `CharteNocturneTest` (contrastes 4,5:1 et 3:1 dans les deux thèmes, animations coupées, pas de CDN de police). Tests de la page d'accueil et des emails mis à jour. Aucun test désactivé.
- [x] Étape 2 : composants alignés sur le handoff (`<x-icone>`, héros, aperçu, fonctionnement en trois étapes, « Ce que tu récupères », « Pour qui ? » avec scènes animées, tarifs, questions en `<details>`, appel final). Bandeau défilant et fiche technique retirés. En-tête collant avec menu mobile. Textes FR et EN mis à jour.
- [x] Thème : sombre par défaut, sans repli sur le thème du système (demande de l'utilisateur ; le handoff prévoyait un repli clair).
- [x] Tests : `PageAccueilTest` adapté au handoff (cartes, lien « Se connecter » dans l'en-tête, animations du bandeau retirées). Tarifs et FAQ liés à la configuration conservés. Liens Tarifs et Questions gardés au pied de page.
- [x] Étape 3 : écrans de l'app alignés sur le handoff. Titres de page en Unbounded (utilitaire `titre-h2`), libellés mono sans majuscules ni espacement. Les cinq états (vide, file, cours, erreur, terminé) reposent sur les statuts existants (`<x-badge-statut>`, `progression-etapes`) : non revus un à un dans un navigateur.
- [x] Étape 5 : thème Filament `resources/css/filament/admin/theme.css` (Onest, rayons 12 px) branché par `viteTheme` dans `AdminPanelProvider`. Palettes `primary` et `gray` de Nocturne données dans `colors()` : Filament les injecte après le CSS du thème. Mode sombre forcé (`darkMode(true, isForced: true)`) : pas de bascule vers le clair. Bouton principal lime à texte encre, titres de page en Unbounded, blanc de la landing (`--color-white`) : surcharges dans `theme.css`. Le titre du formulaire de connexion garde la police par défaut (classe non ciblée). Formulaire de connexion vérifié dans Chrome ; tableau de bord non vérifié (exige un compte administrateur).
- [ ] Vérification visuelle : bureau faite en clair ; mobile 375 px non vérifiée (le redimensionnement de Chrome n'a pas pris effet).
- [x] Maquettes des écrans de l'app ajoutées au handoff (`designs/Voxia Tableau de bord`, `Transcription`, `Historique`, `Compte`, `Connexion`, `Inscription - Nocturne.dc.html`), avec un paragraphe dédié dans le README (états à implémenter, gabarit commun). Elles font foi pour ces six écrans. Vérifiées par lecture du code (états, rôles ARIA, placeholders `[n]` `[limite]` `[date]` pour les quotas et prix), pas rendues dans un navigateur.
- [x] Refonte des pages internes selon les maquettes : en-tête commun (trois entrées, jauge de quota avec barre, langue, déconnexion), largeur de 1120 px et gouttière de 16 px ; tableau de bord en deux colonnes (formulaire, puis cinq transcriptions récentes) ; suivi de traitement avec les trois étapes dérivées du statut ; réglages en ancres avec « Ta formule » (plan, quotas) et zone de suppression en `--vx-error` ; connexion et inscription en deux colonnes avec la colonne décorative. Aucune fonction retirée. Tests : `tests/Feature/Interface/PagesInternesNocturneTest.php` (un par écran et critère). Vérification par tests et lecture du code, pas dans un navigateur (P140).
- [x] Page de transcription (`/transcriptions/{id}`) alignée sur la maquette « Transcription - Nocturne », bloc par bloc : pastille de statut et titre, pastilles de méta, lecteur (YouTube, ou bouton lecture/pause et curseur pour l'audio, onde de 48 barres), recherche avec précédent/suivant, bascules horodatages et suivi, lignes horodatées (phrase en cours, lignes avant discrètes), bandeau d'aperçu, copie, exports (TXT, PDF, SRT, VTT, MP3 ; formats verrouillés « · Pro »), analyse du script en bloc séparé. États en traitement (trois étapes à barre indéterminée, erreur en encadré) et échec. Tests existants conservés, aucun retiré. Vérification par tests et lecture du code ; rendu non vérifié dans un navigateur (pas de transcription de démonstration). Le lecteur audio natif est remplacé par les commandes de la maquette ; l'onde reste figée en pause (comportement existant).
- [x] Connexion et inscription alignées sur les maquettes « Connexion » et « Inscription - Nocturne » : en-tête minimal, champs de 48 px, erreur avec icône et bordure 1,5 px, séparateur « ou avec ton email », bouton Facebook (voir P144). Mise en page des deux écrans via les props de `layouts/auth`. *(Vérifié par les tests, pas encore dans un navigateur.)*
- [x] Pied de page commun (`components/pied-page`) sur l'application, les pages publiques, la connexion, l'inscription et le back-office `/admin` (vue Filament dédiée, aux couleurs Filament). Liens Tarifs, Questions, Conditions, Confidentialité, Contact.
- [x] Historique aligné sur la maquette « Historique - Nocturne » : miniatures 96 × 54, sigles YT / TT / FI, pagination partagée (`app/partials/pagination`), recherche et suppression inchangées. *(Vérifié par les tests, pas encore dans un navigateur ; voir P143.)*
- [ ] Reste à faire : landing, tarifs et thème Filament à reprendre (P142). Voir P138 à P143 et P147.
- Contrôle du lot : `php artisan test` (587 tests : 576 réussis, 11 ignorés, 1 risqué) et `./vendor/bin/pint --test` sans écart.
- [ ] Point connu : `ChaineTranscriptionTest` (cas « MP3 : aucune piste audio ») échoue parfois dans la suite complète et passe seul, avec ou sans ces changements. À reprendre pour trouver la cause (état partagé entre tests, probablement).

## 4. Points ouverts

- Laravel pour l'application web (validé, cahier §8).
- Transcription : ElevenLabs Scribe v2, environ 0,22 $ par heure d'audio (validé).
- Téléchargement derrière une interface, moteur de transcription derrière une interface (cahier §9).
- Devise : dollars US (cahier §7).
- Carte Marketeurs de la maquette gardée telle quelle (cahier §7).
- MP3 inclus dans tous les plans ; mention Pro reformulée « tous les exports, dont PDF et VTT » (cahier §7).
- Base de données : PostgreSQL (demande du projet ; le cahier indique « MySQL ou PostgreSQL »).
- File : Redis + Laravel Horizon, une tâche par vidéo (cahier §8).
- Récupération des vidéos (F1, étape 1) derrière `App\Contracts\VideoFetcher`, implémentée par `YtDlpVideoFetcher` (yt-dlp via `Process`, arguments en tableau).
  - Reconnaissance locale (`ReconnaissanceLien`) : YouTube (watch, youtu.be, Shorts, m.youtube.com) et TikTok (`/@user/video/ID`, `vm.tiktok.com`). Seuls `http` et `https` sont acceptés ; un lien sans schéma est refusé.
  - Lien court `vm.tiktok.com` : l'identifiant reste nul jusqu'aux métadonnées ; le dédoublonnage (F1, 4e critère) devra donc se faire après résolution.
  - Métadonnées avec `--skip-download` ; statut live lu dans `is_live` ou `live_status`.
  - Téléchargement : `-f bestaudio` (pas de conversion, le MP3 est l'étape F3), `--match-filter !is_live` pour ne jamais capturer un live en cours, `--no-playlist`, dossier temporaire par tâche (`voxia.tools.download.temp_directory`), supprimé en cas d'échec.
  - Délais, tentatives et proxy lus dans `config/voxia.php`. Les tentatives ne visent que les erreurs techniques.
  - Traduction des erreurs (`TraducteurErreurYtdlp`) : seules les lignes `ERROR:` sont analysées, pour qu'un titre de vidéo ne fausse pas le motif. Par défaut : `erreur_technique`. La sortie brute va dans les journaux, jamais à l'écran.
  - Commande `voxia:ytdlp-update` (`yt-dlp --update`), planifiée chaque jour à `VOXIA_YTDLP_UPDATE_AT` (04:00 par défaut).
  - Tests : 40 tests (15 formes de liens, traduction des sorties dans `tests/fixtures/ytdlp`, processus simulés). Aucun appel réseau.
  - **Fixtures non enregistrées** : yt-dlp n'était pas installé au moment de l'écriture. Les sorties de `tests/fixtures/ytdlp` sont écrites à partir des messages connus de yt-dlp (voir P17).
- Transcription (F2) derrière `App\Contracts\TranscriptionEngine`, implémentée par `ElevenLabsScribeEngine` (POST `/v1/speech-to-text`, multipart, `model_id` `scribe_v2`, `timestamps_granularity` `word`, en-tête `xi-api-key`). Langue forcée envoyée en `language_code`, sinon absente (détection automatique).
  - Résultat neutre : `TranscriptionBrute` (langue détectée, `MotTranscrit` en millisecondes, texte, coût). Seuls les éléments `word` deviennent des mots ; `spacing` et `audio_event` sont écartés.
  - Tout échec d'appel (clé, quota, taille, délai, réseau, serveur, réponse illisible) est retenté `voxia.transcription.attempts` fois (3), puis devient `erreur_technique`. Choix : les erreurs permanentes sont aussi retentées, comme le demande la consigne ; ne retenter que les erreurs passagères se règle en une ligne.
  - Limites locales (clé absente, fichier au-delà de `max_size_mb`, durée au-delà de `max_duration_seconds`) : échec immédiat, aucun appel.
  - Coût estimé : durée du fichier × `voxia.costs.transcription_usd_per_hour` (0,22), arrondi à 4 décimales.
- Regroupement en paragraphes (`ParagraphBuilder`), séparé du moteur : coupure à une pause ≥ `voxia.paragraphes.pause_ms`, à une fin de phrase une fois `fin_de_phrase_min_mots` atteint, et à `max_mots`. Le paragraphe porte l'horodatage de son premier mot. Les mots sont joints tels quels ; la ponctuation isolée reste collée au mot précédent. Aucune réécriture.
- Tests : `FakeTranscriptionEngine` liée à tous les tests dans `tests/TestCase.php`, avec `Http::preventStrayRequests()`. Les tests du moteur simulent HTTP, sans appel réseau.
- Modèle de données posé (étape 1) : 7 migrations, 7 modèles, 7 enums PHP, factories.
  - Enums : valeurs stockées en `varchar` avec cast Eloquent (pas de type `enum` PostgreSQL, pour ajouter une valeur sans migration lourde). Valeurs en français, comme dans le cahier.
  - Colonnes du domaine en français (`est_apercu`, `motif_echec`…), colonnes techniques en anglais (`user_id`, `created_at`).
  - Mots : une ligne par mot (`transcription_words`), bornes en millisecondes, écrits en lot. Paragraphes calculés (`transcription_paragraphs`), texte joint, lus en une requête pour l'affichage.
  - Recherche plein texte : index GIN sur `to_tsvector('simple', texte)` au niveau paragraphe, sans colonne supplémentaire. Le résultat renvoie le paragraphe et son `debut_ms`, donc l'extrait et le lien vers l'horodatage. Une transcription de 3 heures compte environ 30 000 mots mais seulement quelques centaines de paragraphes : l'affichage ne charge pas les mots. Le test vérifie avec `EXPLAIN` que l'index est utilisé.
  - Dédoublonnage F1 : contrainte unique `(user_id, source, id_video)`. Un fichier importé (`id_video` nul) n'est pas concerné.
  - Une seule analyse de script par transcription (contrainte unique). Relancer une analyse réutilise la ligne.
  - Journal de consommation (`usage_records`) jamais modifié. Un remboursement est une ligne en sens inverse (`annule_usage_record_id`), une seule fois par consommation ; une ligne de remboursement ne peut pas être annulée (`UsageRecord::reverse()`).
  - Suppression d'une transcription : mots, paragraphes, analyse et journaux supprimés ; la ligne de consommation reste, avec `transcription_id` nul, pour que le quota ne se libère pas.
  - `is_admin` n'est pas assignable en masse. Langue de l'interface par défaut : `fr` (à confirmer, voir P15).
  - Tests : 32 tests de modèle. Sur SQLite (config par défaut), 28 passent et 4 recherche plein texte sont ignorés. Sur PostgreSQL 16 (base `voxia_test`), les 32 passent.
- Extraction audio (F3) derrière `App\Contracts\AudioExtractor`, implémentée par `FfmpegAudioExtractor` (ffprobe et FFmpeg via `Process`, arguments en tableau). Pas de fausse implémentation : FFmpeg est local et les tests génèrent de vrais petits fichiers, sans réseau.
  - Format vérifié par ffprobe, jamais par l'extension. Familles acceptées : MP3, WAV, WEBM (matroska), et MP4/M4A/MOV, avec 3GP (ffprobe ne les distingue pas, donc une seule famille). Un fichier illisible ou hors liste donne `format_non_supporte`.
  - Motif `format_non_supporte` ajouté (hors cahier) : texte fr et en dans `lang/`. À valider (P20).
  - Sans piste audio : `sans_audio`. Source absente ou échec de FFmpeg : `erreur_technique`, aucun fichier partiel.
  - Première piste audio seulement (`-map 0:a:0`) : pas de vidéo ni d'image de couverture. Titre écrit dans les métadonnées `title`, sans autres métadonnées.
  - Débit : `voxia.audio.mp3_bitrate_kbps` (192). Durée du MP3 mesurée par ffprobe, arrondie à la seconde.
  - Découpe aux N premières secondes (`-t`) : sert à l'aperçu de 10 minutes du plan gratuit. Pas encore branchée au plan ; la limite de 10 minutes n'est pas encore appliquée par ce service.
  - Nom de fichier (`NomFichierAudio`) : accents conservés en forme composée (NFC, pour macOS) ; caractères `< > : " / \ | ? *` et contrôles retirés (pas remplacés) ; espaces réduits ; points et espaces retirés en début et fin ; noms réservés Windows (CON, PRN, AUX, NUL, COM1-9, LPT1-9) préfixés par « _ » ; titre vide : `audio.mp3`. Longueur limitée à 200 octets (`voxia.audio.filename_max_bytes`), sans couper un caractère UTF-8.
  - Tests : 39 tests (23 sur le nom, dont 10 titres piégés ; 16 sur l'extraction). Réglages des délais dans `voxia.audio`.

- Chaîne de traitement (`App\Services\Traitement\ChaineTranscription`, tâche `App\Jobs\ProcessTranscription`) : métadonnées → téléchargement → MP3 → transcription → paragraphes → enregistrement.
  - Une ligne `processing_logs` par étape, réussie ou non, avec durée, message et coût (coût renseigné à la transcription uniquement).
  - Statut par phase : Téléchargement dès le début (métadonnées incluses), Transcription avant le moteur, Terminé à l'enregistrement, Échec avec motif. Les dates suivent chaque étape.
  - Le MP3 final est stocké à l'étape « enregistrement », sur le disque `voxia.stockage.disque` (`local` par défaut, disque S3 en production), dans `transcriptions/{id}/`. Il est retiré si l'écriture en base échoue, et jamais conservé pour une transcription en échec.
  - Tentatives : métadonnées (`voxia.traitement.tentatives.metadonnees`, 2) et stockage (3), uniquement sur erreur technique. Téléchargement et transcription gardent leurs propres tentatives (3 chacun). Aucune nouvelle tentative au niveau de la tâche, pour ne pas retélécharger.
  - Délais : chaque étape a le sien (outils, moteur, FFmpeg). Garde-fou : `voxia.traitement.delai_total_secondes` (7 200 s), supérieur à la somme des délais d'étapes. Une tâche interrompue passe en Échec via `failed()`.
  - Nettoyage : dossier du téléchargement supprimé par `AudioTelecharge::supprimer()` et dossier du MP3 supprimé dans un `finally`. Le fichier source d'un fichier local n'est jamais supprimé.
  - Événements : `TranscriptionCompleted` (notifications, dont l'e-mail des vidéos longues) et `TranscriptionFailed(motif)` (point d'accroche des notifications). Le décompte n'est pas fait par un écouteur : il est écrit dans la même transaction que le passage à « Terminé », pour qu'une transcription terminée soit toujours décomptée et qu'un décompte refusé la fasse échouer.
- Files : `ProcessTranscription` va sur `high` pour le plan Pro, `default` pour Gratuit et Pack (`voxia.traitement.plans_prioritaires`). Horizon sert les files dans l'ordre listé, avec `balance => false`.
- Plan de l'utilisateur : colonne `users.plan` (défaut `free`), enum `App\Enums\Plan`. Ajoutée ici car le routage des files en dépend ; la logique de plan et de quota est faite (F6, voir ci-dessous).
- Quotas (F6) : `App\Services\Quota\QuotaService` et `App\Services\Plans\PlanFeatures`. Toutes les valeurs viennent de `config/voxia.php`.
  - Gratuit : 1 vidéo = 1 vidéo, quelle que soit sa durée ; le compteur repart le 1er du mois, en UTC.
  - Pro : durée arrondie à la minute supérieure ; cycle à la date anniversaire de `users.abonnement_debut_at` (fin de mois ramenée au dernier jour) ; crédits IA mensuels.
  - Pack : table `pack_achats` (secondes, `achete_at`, `expire_at` = achat + 6 mois), créée par `App\Services\Quota\AchatPack`. Un Pack non expiré compte ; le plus ancien est consommé en premier. Un compte Gratuit qui achète passe au plan Pack ; un abonné Pro garde son plan.
  - Une vidéo consomme d'abord le volume Pro du cycle, le reste vient des Packs (lignes séparées, une par Pack). Business reste défini mais non vendable (`PlanFeatures::plansVendables()`).
  - Journal `usage_records` : colonnes `plan`, `periode` (début du cycle) et `pack_achat_id`. Un remboursement (`reverse()`) les recopie, pour rendre les secondes à la bonne période et au bon Pack.
  - Concurrence : lancement, vérification au traitement et décompte prennent un verrou sur la ligne du compte (`lockForUpdate`). Les transcriptions en cours comptent comme réservées (durée connue, une minute si elle ne l'est pas). Le décompte final est refait sous verrou : il ne peut pas dépasser le quota. Voir P96.
  - Refus : durée au-delà de la limite d'un plan payant, refusée avant création (`trop_longue`) ; quota épuisé, `quota_epuise` (au lancement ou au traitement, sans décompte).
  - Crédits IA : Pro seulement. Gratuit et Pack sont refusés avant tout appel (`credit_ia_insuffisant`). Un crédit par analyse réussie.
  - Compteur : composant `x-compteur-plan`, dans `layouts/app` pour tout compte connecté. Textes : « 3 / 5 vidéos ce mois-ci », « 12 h 40 restantes ce mois-ci », « 10 h en Pack », « 24 crédits IA » (`interface.quota`).
  - `FileTraitement` est remplacé par `PlanFeatures::fileDe()` (Pro en `high`, les autres en `default`).
  - Tests : `tests/Feature/Quotas/QuotasTest.php`, une règle par test. `Tests\TestCase` active `voxia.droits.plan_reel` : les tests lisent le plan enregistré sur le compte. Les tests d'analyse de script utilisent un compte Pro.
- Durée maximale : la chaîne refuse une vidéo plus longue que `max_video_seconds` du plan (lien : après métadonnées ; fichier : après extraction).
- Lancement (`App\Services\Traitement\LancerTranscription`) : lien reconnu puis mis en file, ou fichier local dont l'extension est dans `voxia.uploads.extensions`. Une transcription déjà terminée est renvoyée ; une transcription en échec est relancée sur la même ligne ; une transcription en cours n'est pas relancée.
- Commande `voxia:transcribe {source} {--user=email} {--langue=}` : lance, suit les étapes au fil de l'eau, puis affiche les 10 premiers paragraphes. `--user` est obligatoire : la commande n'a pas de compte implicite.
- Tests : 27 nouveaux tests (`tests/Feature/Traitement`, suite passée de 131 à 158) avec `FauxVideoFetcher` (échec par étape, file de métadonnées), `FauxAudioExtractor` (nouveau, sans FFmpeg) et `FakeTranscriptionEngine`. Couverts : succès, échec à chaque étape, vidéo privée, live, durée du plan, nouvelles tentatives, fichier local, tâche abandonnée, nettoyage des fichiers temporaires, file par plan, déduplication, commande.
- Charte Signal (cahier §7), jetons dans `resources/css/app.css` (`@theme`) :
  - Valeurs exactes de §7. L'accent est la variable unique `--color-accent` (tester #C8F542 ou #6E8BFF en changeant cette seule ligne).
  - Rayons 4, 6 et 10 px (`rounded-bouton`, `rounded-champ`, `rounded-panneau`).
  - Polices auto-hébergées par `@fontsource` (npm), importées dans `resources/js/app.js`. Le chargement depuis Bunny (`laravel-vite-plugin/fonts`) est retiré de `vite.config.js` : aucun appel à un CDN de polices. Un test vérifie l'absence de `fonts.googleapis`, `fonts.bunny` et équivalents.
  - Jetons ajoutés hors §7, à valider : `--color-filet-contour` (#6A6B65, contour de champ et de bouton à filet, 3:1 minimum) et `--color-erreur` (#FF7A73, messages d'erreur et statut Échec).
  - Composants dans `resources/views/components` : `x-bouton` (variantes principal, clair, filet), `x-champ`, `x-panneau`, `x-badge-statut` (accepte `StatutTranscription`), `x-horodatage` (Geist Mono, formaté par `App\Support\Horodatage`), `x-message-erreur`, `x-compteur-quota` (valeurs passées par l'appelant), `x-onde-audio`, `x-fenetre-superposee` (dialog natif), `x-marque`.
  - Mises en page dans `resources/views/layouts` (`<x-layouts::marketing>`, `<x-layouts::app>` avec emplacements `quota` et `langue`). Espace de noms enregistré dans `AppServiceProvider`.
  - Animations (onde, pulsation des statuts) coupées sous `prefers-reduced-motion`.
  - Page `/styleguide`, enregistrée seulement si `app()->isLocal()`.
  - Pas de dégradé, pas d'ombre portée, pas d'emoji. La grille de fond de la maquette (dégradé de lignes) n'est pas reprise.
  - Tests : `tests/Feature/Interface/CharteSignalTest.php` (contrastes lus dans les jetons, animations, CDN, rendu du styleguide) et `tests/Unit/Support/HorodatageTest.php`.

- Paiement (étape 4) : Paddle via Laravel Cashier Paddle 2.8 (choix par défaut du prestataire, derrière `App\Contracts\BillingProvider`). Seule `App\Services\Paiement\PaddleBillingProvider` parle à Paddle ; `FauxBillingProvider` est lié dans `Tests\TestCase`. Sandbox uniquement (`PADDLE_SANDBOX=true`, valeur par défaut).
  - Webhooks : `POST /paddle/webhook` (`App\Http\Controllers\Paiement\WebhookPaddleController`, hérite du contrôleur Cashier). Cashier vérifie la signature et met à jour ses tables `subscriptions`, `subscription_items`, `transactions`. Sans `PADDLE_WEBHOOK_SECRET`, tout webhook est refusé (403). Route exclue du CSRF (`bootstrap/app.php`).
  - Idempotence en deux niveaux : table `evenements_paiement` (`event_id` unique), puis effets eux-mêmes idempotents (`pack_achats.paddle_transaction_id` unique ; plan et articles recalculés depuis l'état, pas ajoutés).
  - Traitement en file (`ProcesserEvenementPaiement`, 6 tentatives), jamais dans la requête web. Le webhook est enregistré après le traitement de Cashier, pour que le plan lise l'abonnement à jour, y compris en file synchrone.
  - Plan calculé par `App\Services\Paiement\SynchroniserPlan` : Pro si abonnement valide (actif, ou résilié dans la période payée), sinon Pack si un Pack est valable, sinon Gratuit. L'ancre du cycle (`abonnement_debut_at`) est posée au passage à Pro.
  - Aperçu partiel : `LancerTranscription::relancerVersionComplete()` remet la même ligne en file, sans toucher au décompte Gratuit déjà fait. Refusé si l'aperçu est encore en cours : la tâche réessaie plus tard.
  - Impayé (`past_due`) : Pro retiré tout de suite, comportement par défaut de Cashier (`Cashier::$deactivatePastDue`). Décision à confirmer (P100).
  - `users.plan` n'est plus lu sur un drapeau : `voxia.droits.plan_reel` vaut `true` par défaut.
  - Pages : `/paiement/{produit}` (formules `pro_mensuel`, `pro_annuel`, `pack`, email vérifié), `/abonnement`. Le bandeau d'aperçu renvoie vers `/paiement/pro_mensuel?transcription=…`.
  - Paddle.js ouvre l'overlay côté navigateur ; aucune donnée de carte ne passe par Voxia.
  - Dépendances : `laravel/cashier-paddle` ajouté. Il impose Guzzle 7 (la version 8.3.1 était verrouillée). Le test `ElevenLabsScribeEngineTest` a été ajusté : l'en-tête `Content-Length` de chaque partie multipart casse l'ancienne expression, pas le comportement testé.
  - Migrations Cashier publiées dans `database/migrations` (`vendor:publish --tag=cashier-migrations`), plus `creer_evenements_paiement` et `ajouter_transaction_paddle_aux_packs`.
  - Recalcul des plans : `voxia:recalculer-plans`, chaque jour à `VOXIA_PLANS_RECOMPUTE_AT` (03:45 par défaut).

## 4. Points ouverts

- [ ] P0 — Critères d'acceptation de F6 (offre gratuite) : à rédiger, le cahier n'en fournit pas.
- [ ] P1 — L'extraction MP3 seule, sans transcription, décompte-t-elle une vidéo en gratuit ?
- [ ] P2 — Durée de conservation des MP3 et des fichiers importés (proposition : 30 jours). Branchée dans `voxia.retention.media_days` (30 jours par défaut) ; la valeur reste à valider.
- [ ] P3 — Taille maximale d'un fichier importé (proposition : 2 Go).
- [ ] P4 — Structure juridique qui encaisse : Paddle retenu par défaut (voir §3, Paiement). Stripe écarté pour l’instant (accès depuis le Burkina Faso à vérifier). Compte Paddle et structure juridique à confirmer.
- [ ] P5 — Langues de l'interface au lancement (proposition : anglais et français).
- [ ] P6 — Autoriser un premier essai sans compte depuis la page d'accueil ?
- [ ] P7 — Nombre de crédits IA inclus chaque mois dans le Pro ; une analyse d'essai en Gratuit pour déclencher l'upgrade ? *(Valeur provisoire : 30 par mois dans `voxia.plans.pro.ai_credits_per_month`. Une analyse d'essai en Gratuit reste à décider : aujourd'hui le Gratuit n'a pas d'analyse.)*
- [x] P8 — Fournisseur du modèle de langage utilisé pour F7 : API Claude (Anthropic), modèle `claude-haiku-5-5`, sorties structurées. Retenu dans la demande de F7 ; reste à vérifier avec une vraie clé (P85).
- [ ] P9 — Durée maximale d'une vidéo analysable (proposition : 3 minutes).
- [ ] P10 — Avis juridique sur la récupération des vidéos YouTube et TikTok, et rédaction des CGU et de la politique de confidentialité (texte définitif à demander, ne pas inventer).
- [ ] P11 — Suppression du compte : les lignes de consommation (`usage_records`) sont supprimées avec le compte, comme le demande le cahier §5. Faut-il les conserver pour la facturation ou les obligations comptables ? Question juridique, à trancher avec l'avis du P10.
- [ ] P12 — Règle de regroupement en paragraphes : valeurs proposées, à valider. Pause 1 200 ms, fin de phrase à partir de 20 mots, longueur maximale 60 mots (`voxia.paragraphes`).
- [ ] P13 — Recherche sans accents ni racinisation : la configuration `simple` ne reconnaît ni « école » pour « ecole », ni les formes conjuguées. Pistes : configuration `french` / `english` selon la langue détectée, ou extension `unaccent`. À trancher avec des données réelles.
- [ ] P14 — Statuts de l'analyse de script (`en_attente`, `analyse`, `termine`, `echec`) : proposés par le modèle, absents du cahier. À valider.
- [ ] P15 — Langue de l'interface par défaut (`fr` dans le modèle, `APP_LOCALE=en` dans `.env`). À confirmer avec P5.
- [ ] P16 — Essai réel de F1 non fait : yt-dlp absent de la machine et téléchargement refusé. Reste à lancer `voxia:ytdlp-update` puis une lecture de métadonnées sur un lien YouTube public et un lien TikTok public. Les liens de test n'ont pas été fournis (placeholders restés dans la demande).
- [ ] P17 — Fixtures yt-dlp écrites à partir des messages connus, pas enregistrées. À remplacer par de vraies sorties (privée, supprimée, région, live).
- [ ] P18 — Vidéos avec restriction d'âge ou connexion requise : classées en erreur technique (aucun décompte). Faut-il un motif dédié ?
- [ ] P19 — Erreurs génériques « Video unavailable » ou « not available » : classées en erreur technique par prudence. Affiner quand une vraie sortie sera enregistrée.
- [ ] P20 — Formats hors liste (MKV, OGG, AAC brut) refusés par contenu avec `format_non_supporte`. Le 3GP passe avec la famille MP4, faute de moyen fiable de le distinguer. Le cahier ne dit rien de ces formats ; à confirmer.
- [ ] P21 — Nom de fichier : « Partie 1/2 » devient « Partie 12 » (caractère retiré, pas remplacé). Le cahier demande de retirer ; une alternative serait un tiret. À confirmer.
- [ ] P22 — Fixtures de F2 : `francais_redige.json`, `anglais_redige.json` et `discours_sans_pause_redige.json` sont écrites à la main au format documenté, non enregistrées (même statut que P17). Seule `muette_enregistree.json` vient d'un vrai appel (un bip de 2 s). À remplacer dès qu'un MP3 parlé est disponible.
- [ ] P23 — Essai réel de F2 sur 5 paragraphes : non fait. Le MP3 disponible (`storage/framework/testing/audio/sortie/CON vidéo test.mp3`, 2 s) ne contient qu'un bip ; ElevenLabs renvoie `[beep]` sans mot, donc aucun paragraphe. Il faut un MP3 parlé (français, puis anglais).
- [ ] P24 — Une clé ElevenLabs a été collée dans la conversation. Elle est enregistrée dans `.env` (ignoré par Git, jamais commitée). À régénérer si la conversation est partagée.
- [ ] P25 — Codes d'erreur de Scribe : `invalid_api_key` (401), `quota_exceeded` et `too_many_concurrent_requests` / `system_busy` (429) viennent d'articles d'aide ElevenLabs. Les codes 413 et 422 ne sont pas documentés pour la transcription ; la page des erreurs de la doc a renvoyé 403. À vérifier sur les réponses réelles.
- [ ] P26 — Taille maximale d'un fichier transcrit : 3 Go (FAQ officielle, retenu) contre 5 Go (page API). Durée maximale : 10 h en mode standard.
- [ ] P27 — Espace typographique française (« Bonjour ? ») : les mots sont joints par une espace et le moteur ne renvoie pas les espaces. À vérifier sur un vrai texte français.
- [ ] P28 — Abréviations (« M. Dupont », « etc. ») : une fin de phrase peut couper à tort. Non géré.
- [ ] P29 — Redis et Horizon non testés en vrai : `redis-cli` absent, et `QUEUE_CONNECTION` du `.env` non vérifié (le `.env` n'a pas été lu). Les tests passent en file `sync`. Reste à vérifier : `QUEUE_CONNECTION=redis`, `php artisan horizon` qui sert `high` puis `default`.
- [ ] P30 — Arrêt brutal du worker (délai total, kill) : `failed()` passe la transcription en Échec, mais les dossiers temporaires de téléchargement créés par `VideoFetcher` ne sont pas connus et restent. Piste : un balayage des dossiers temporaires anciens, planifié.
- [ ] P31 — Source « fichier » : le chemin doit être lisible par le worker. Avec un worker sur une autre machine, il faudra passer par le stockage (upload) ; pas traité ici.
- [ ] P32 — Lien court `vm.tiktok.com` : pas de déduplication à la création (identifiant inconnu avant résolution). Un doublon est possible jusqu'à l'étape métadonnées, où une collision sur `(user, source, id_video)` fera échouer la tâche en erreur technique. À traiter.
- [ ] P33 — Étape « paragraphes » : aucun échec métier possible, donc pas de test d'échec pour elle (seulement le succès).
- [x] P34 — Résolu par P64 : au Gratuit, une vidéo plus longue reçoit un aperçu de 10 minutes, sans refus. Constat d'origine : la chaîne applique `max_video_seconds` (10 min en Gratuit). Confirmer que refuser une vidéo de 15 min en Gratuit est bien le comportement voulu (cahier F1 parle d'un email au-delà de 15 min, pas d'un refus).
- [ ] P35 — Noms de files codés en dur dans `config/horizon.php` (`high`, `default`), à garder synchronisés avec `voxia.traitement.files`.
- [x] P36 — Essai réel de la chaîne sur 6 liens publics (4 YouTube, 2 TikTok), dont une vidéo d'une heure (compte Pro) : 6 transcriptions terminées, voir `docs/mesures-etape-1.md`. Reste à faire : vidéo privée, vrai lien supprimé, et un échantillon d'environ 60 liens pour valider la cible de 95 %.
- [ ] P37 — `voxia:transcribe` plante au premier suivi : import `Illuminate\Support\Facades\Sleep` inexistant (à remplacer par `Illuminate\Support\Sleep`). Les transcriptions aboutissent quand même dans la file.
- [ ] P38 — Horizon ne démarre pas sous Windows (`pcntl_async_signals` absente). Tests faits avec `queue:work`. Horizon à vérifier sous Linux ou WSL.
- [ ] P39 — Une erreur « Object of class SourceTranscription could not be converted to string » est apparue une fois dans le worker, sans trace dans `laravel.log`. Non reproduite ; à surveiller.
- [ ] P40 — Couleur d'erreur #FF7A73 : absente du cahier §7, proposée ici (7,1 à 7,6:1 sur les trois fonds). À valider.
- [ ] P41 — Contour de champ et de bouton à filet #6A6B65 : absent du cahier §7. Le filet #2A2B28 ne fait que 1,3:1 sur le fond, sous le seuil de 3:1 pour un contour de contrôle. À valider.
- [ ] P42 — Gris #6E6D68 de la maquette (horodatages, libellés) : 3,5 à 3,7:1, échec de 4,5:1 sur les trois fonds. Remplacement proposé : #81807A (4,5 à 4,9:1). À valider avant intégration de la maquette.
- [ ] P43 — Bandeau défilant et frappe du texte (animations §7) : non construits, aucun composant demandé. Les animations existantes (onde, pulsation) sont coupées sous `prefers-reduced-motion`.
- [ ] P44 — Rendu visuel non vérifié dans un navigateur : l'extension Chrome n'était pas connectée. Vérifié seulement par rendu Blade, génération Tailwind et tests. À regarder sur `/styleguide` en local.
- [ ] P45 — Sélecteur de langue du layout `app` : emplacement seulement, pas de composant. *(Les réglages du compte proposent la langue ; le sélecteur de l'en-tête reste à faire.)*
- [x] P46 — Réinitialisation : une adresse sans compte reçoit « Aucun compte ne correspond à cette adresse » (message Laravel). Cela révèle quelles adresses ont un compte. Accepté tel quel le 3 novembre 2026 (pas de message neutre unique).
- [x] P47 — Limite de comptes gratuits par IP : fixée à 2 (`VOXIA_FREE_ACCOUNTS_PER_IP`, validée le 3 novembre 2026). Vide = aucune limite. Limite par appareil non traitée.
- [ ] P48 — Adresse IP de création (`users.inscription_ip`) : donnée personnelle conservée pour la limite. Durée de conservation et mention RGPD à fixer avec P10. Derrière un proxy, l'IP lue doit être la vraie (proxys de confiance à configurer).
- [ ] P49 — Sixième tentative de connexion : réponse 429 générique (page du framework, en anglais), sans le message « trop de tentatives » traduit.
- [ ] P50 — Compte Google sans mot de passe : les réglages demandent le mot de passe actuel. Pour en créer un, passer par « Mot de passe oublié ». Pas de message dédié.
- [ ] P51 — Lien en attente : retiré du compte avant le lancement. Si le lancement échoue, le visiteur voit « Ton lien n'a pas pu être lancé » sans motif et doit le coller à nouveau.
- [ ] P52 — Connexion Google : jamais essayée avec un vrai client Google. Il faut créer les identifiants dans la console Google Cloud et déclarer l'URI de redirection `/auth/google/callback`.
- [ ] P53 — Suppression : si la file perd la tâche `SupprimerFichiersTranscriptions`, les MP3 restent sur le disque sans ligne en base pour les retrouver. Piste : journal des dossiers à purger, ou balayage périodique.
- [ ] P54 — Traduction française du framework partielle (`lang/fr/validation.php`) : les règles non listées restent en anglais.
- [ ] P55 — Emails : pas encore vus dans Mailpit ni dans une boîte réelle. Pas de pied de page légal (CGU, confidentialité, voir P10).
- [ ] P56 — Écrans de compte non vérifiés dans un navigateur : rendu seulement contrôlé par tests (même statut que P44).
- [ ] P57 — PostgreSQL (127.0.0.1:5433) ne répond pas sur ce poste (délai dépassé à la connexion). Tests faits sur SQLite. Le serveur de démonstration de l'écran tourne sur SQLite, file synchrone, cache et sessions en fichiers, sans modifier `.env`. À refaire sur PostgreSQL et Redis quand le service répond.
- [ ] P58 — Rendu de l'écran à 375 px et 1440 px : non vérifié par moi (pas de contrôle visuel dans cette session). À regarder sur `http://127.0.0.1:8000/tableau-de-bord` (compte de test local, hors dépôt).
- [x] P59 — Morceaux orphelins : un envoi interrompu laisse son dossier de morceaux dans `storage/app/televersements`. Résolu par `voxia:purger-medias` (même règle d'âge que les fichiers importés, dossiers vides retirés).
- [x] P60 — Fichiers importés : rétention branchée par `voxia:purger-medias` (durée `voxia.retention.media_days`, par date de modification). Voir P71 pour la limite de cette règle.
- [x] P61 — Liste des langues forçables (fr, en, es, de, it, pt) : validée avec ElevenLabs Scribe v2 (codes ISO 639-1, acceptés par `language_code`). Scribe v2 couvre plus de 90 langues, donc la liste est un sous-ensemble.
- [ ] P62 — Aperçu via la file : sans worker, l'écran reste sur « Lecture des informations de la vidéo… ». Sur Windows, Horizon ne démarre pas (P38) ; il faut `php artisan queue:work` pour le voir se remplir.
- [ ] P63 — Envoi interrompu : pas de reprise, le fichier repart du début avec un nouvel identifiant. Une reprise par morceau demanderait de garder la liste des morceaux reçus côté serveur.
- [x] P64 — Gratuit, vidéo de plus de 10 minutes : aperçu de 10 minutes avec invitation au Pro, au lieu du refus (décision validée). Reste à faire avec F6 : relancer en version complète après paiement, et le décompte d'une vidéo.
- [ ] P67 — Gratuit : une vidéo longue dont ffprobe ne donne pas la durée source (0 s) est transcrite sur 10 minutes sans marque d'aperçu. Cas rare, à surveiller sur les vrais fichiers.
- [ ] P65 — Décompte : aucune ligne `usage_records` n'est écrite par le lancement. Un échec technique ne décompte donc rien, mais parce que le décompte n'existe pas encore : le critère ne sera vraiment vérifié qu'avec F6.
- [ ] P66 — E-mail « vidéo prête » : vérifié par tests (Mail::fake) seulement, jamais reçu dans Mailpit ni une boîte réelle (même statut que P55).
- [ ] P68 — PostgreSQL du poste : le port 5433 du `.env` est relayé par Docker Desktop, arrêté ce jour-là, et le mot de passe du `.env` n'est plus accepté. Les tests PostgreSQL ont tourné sur une instance temporaire (PostgreSQL 18, port 5434, données dans le dossier scratchpad de la session, hors dépôt). À relancer sur la base du projet avec les bons identifiants.
- [ ] P69 — Test de charge F5 : 150 à 185 ms mesurés sur le poste de développement (Windows, PostgreSQL 18 local, pas de serveur de production). À repasser sur la base de recette avant la bêta.
- [ ] P70 — Statistiques PostgreSQL : après un import massif et avant `ANALYZE`, le planificateur peut choisir un plan lent pour d'autres requêtes. La recherche est protégée par le CTE `MATERIALIZED` ; autovacuum doit tourner en production (à vérifier au déploiement).
- [ ] P71 — Fichiers importés : la rétention se fait par date de modification, car le chemin source n'est pas enregistré sur la transcription. Un fichier envoyé il y a 31 jours et transcrit hier serait donc retiré. Enregistrer le chemin à la création si la règle doit viser la transcription.
- [ ] P72 — Suppression pendant le traitement : la chaîne peut encore écrire le MP3 d'une transcription supprimée (retiré si l'écriture en base échoue). Non testé sur la file réelle (P29).
- [ ] P73 — Rappel avant suppression : textes d'email à valider (ton, mention des 30 jours). Jamais reçu dans Mailpit ni une boîte réelle (même statut que P55 et P66).
- [ ] P74 — Extrait de recherche : `ts_headline` écarte certaines balises HTML de la transcription, si bien que l'extrait peut différer du texte brut. Sans effet sur la sécurité (le texte est échappé) ; à valider sur de vrais textes.
- [x] P75 — Droits par plan : `VOXIA_PLAN_REEL` vaut `true` par défaut depuis la facturation (étape 4).
- [ ] P76 — Historique : écrans non vérifiés dans un navigateur à 375 px (mêmes statuts que P44 et P58), dont le bouton de confirmation de suppression.
- [ ] P77 — Pagination : précédente et suivante seulement (pas de numéros de page) pour rester lisible au téléphone. À confirmer avec la maquette.
- [ ] P78 — Import réel des SRT et VTT dans YouTube Studio et CapCut : à faire à la main avec `storage/app/exports-test/exemple.srt` et `exemple.vtt`. Le validateur de règles ne remplace pas ce test.
- [ ] P79 — Export PDF pendant la requête web : contraire à la règle « tout passe par la file » (CLAUDE.md §5). Mesuré sur le poste de développement, pour 28 848 mots (3 heures) : PDF 14,5 s, SRT 0,45 s, TXT moins de 0,02 s. Décision à prendre : passer le PDF par la file avec téléchargement différé, ou garder la requête si la mesure le permet.
- [ ] P80 — Écritures sans couverture dans le PDF : arabe et CJK. La mise en forme de Noto Sans Arabic échoue dans mPDF (table GPOS de type 5 non prise en charge). TXT, SRT et VTT les gèrent. Choix à faire : police CJK plus lourde, ou moteur PDF différent.
- [x] P81 — Bouton MP3 de la barre d'export : branché sur un lien signé (voir §3, « Téléchargements »), testé.
- [ ] P82 — Durée des liens signés (MP3 et exports) : 15 minutes, valeur reprise de l'ancien code (`voxia.telechargement.duree_lien_minutes`, variable `VOXIA_LIEN_DUREE_MINUTES`). Pas dans le cahier : à valider.
- [ ] P83 — Lien expiré : une page ouverte depuis plus de 15 minutes donne un 403 au clic sur MP3 ou sur un export. Piste : régénérer les liens au clic (route ou Livewire) au lieu de les figer au rendu.
- [ ] P84 — Lecteur audio : son adresse reste la route authentifiée (`transcriptions.audio`), pas un lien signé, pour qu'un seek ne tombe pas sur un lien expiré en pleine lecture.
- [ ] P85 — Analyse de script (F7) : aucun appel réel à l'API Claude (pas de clé dans l'environnement de développement). Le délai de 15 s pour une pub de 60 s et le coût réel par analyse (tarif Haiku 5.5 en `config/services.php` et `voxia.costs`, à vérifier) restent à mesurer.
- [ ] P86 — Analyse de script (F7) : les 20 pubs de référence ne sont pas fournies. Format proposé pour `tests/fixtures/pubs-reference.json` : `{ "version": 1, "pubs": [ { "id", "titre", "langue", "duree_ms", "mots": [ { "texte", "debut_ms", "fin_ms" } ], "parties": { "accroche", "probleme", "preuve", "offre", "appel_action" } } ] }`, où chaque partie vaut `{ "debut_ms", "fin_ms" }` ou `null` si absente. Les mots viennent d'une vraie transcription. Sans ce fichier, `voxia:eval-scripts` s'arrête avec un message clair.
- [ ] P87 — Analyse de script (F7) : la lecture au clic sur une partie n'est pas vérifiée dans un navigateur réel (le test contrôle l'événement envoyé au lecteur). À faire avec l'application lancée, sur YouTube et sur un MP3.
- [x] P88 — Analyse de script (F7) : le décompte de crédit IA ne bloque rien ni ne vérifie le plan (Gratuit, Pack) avant l'étape 4. *(Fait avec F6 : `QuotaService::verifierCreditIa` refuse Gratuit et Pack, et réserve les analyses en cours.)*
- [ ] P89 — Analyse de script (F7) : la liste de l'historique charge l'analyse de chaque ligne (requête par ligne, au plus 20 lignes par page). À regrouper avec `with('scriptAnalysis')` si la liste grossit.
- [ ] P90 — Analyse de script (F7) : la recherche plein texte ne reprend pas le découpage des parties ; elle affiche seulement la pastille « Script analysé ». Un filtre par partie reste à décider.
- [ ] P91 — Suite de tests : `memory_limit` relevé à 512 Mo dans `phpunit.xml`. Avec 128 Mo, la suite complète échoue à `tests/Support/ValidateurSousTitres.php` (mémoire cumulée des rendus PDF et des transcriptions de test). À réduire plutôt qu'à masquer si la suite grossit encore. Un MP3 renvoyé par `Storage::temporaryUrl` (disque S3) n'a pas été essayé avec un vrai bucket.
- [ ] P92 — Quotas (F6) : le cycle du Pro part de `users.abonnement_debut_at`. Sans cette date, la création du compte sert d'ancre. Le paiement (étape 4) doit la renseigner à la souscription. À confirmer. *(Ancre posée au passage à Pro : date de création de l'abonnement chez Paddle, `AppServicesPaiementSynchroniserPlan`. Reste à confirmer.)*
- [ ] P93 — Quotas (F6) : un compte Gratuit qui achète un Pack passe au plan Pack (choix fait ici). Alternative : garder le Gratuit et lui ouvrir les Packs. À confirmer.
- [ ] P94 — Quotas (F6) : règle demandée, à confirmer. Un abonné Pro qui achète un Pack consomme d'abord son volume Pro, puis le Pack, y compris pour une vidéo qui chevauche les deux.
- [x] P95 — Quotas (F6) : `voxia.droits.plan_reel` vaut `true` par défaut (voir P75).
- [ ] P96 — Quotas (F6) : la concurrence réelle n'est pas testée automatiquement. Les tests tournent sur SQLite, où `lockForUpdate` n'a aucun effet. À vérifier sur PostgreSQL, avec deux lancements en parallèle.
- [ ] P97 — Quotas (F6) : un fichier importé n'a sa durée qu'après l'extraction. Au lancement, seule une minute est réservée ; le contrôle réel a lieu au traitement, et un dépassement échoue en `quota_epuise` sans décompte.
- [ ] P98 — Paiement : produits à créer dans le sandbox Paddle (un produit « Voxia Pro » à deux prix récurrents, USD : 12 $ mensuel et 96 $ annuel ; un produit « Voxia Pack » à un prix unique, USD : 5 $). Identifiants `pri_…` à reporter dans `VOXIA_PADDLE_PRICE_PRO_MONTHLY`, `VOXIA_PADDLE_PRICE_PRO_YEARLY` et `VOXIA_PADDLE_PRICE_PACK`.
- [ ] P99 — Paiement : les montants affichés (`voxia.plans.*`) et les montants des prix Paddle sont deux réglages distincts. Rien ne vérifie qu'ils concordent ; à contrôler à la main avant la mise en vente.
- [ ] P100 — Paiement : un abonnement impayé (`past_due`) perd l'accès Pro tout de suite (défaut de Cashier). Choix possible : garder l'accès pendant les relances de Paddle. À trancher avant la vente.
- [ ] P101 — Paiement : la page d'abonnement ne reflète une formule modifiée qu'à l'arrivée du webhook (quelques secondes). Aucun rafraîchissement n'est prévu.
- [ ] P102 — Paiement : aucun test contre le sandbox Paddle réel (pas de clé ici). Points à vérifier : signature des webhooks, format réel des données (`custom_data`), overlay Paddle.js, dans cet ordre.
- [ ] P103 — Paiement : après relance d'un aperçu, l'ancien MP3 de l'aperçu reste sur le disque jusqu'à la purge de la transcription (`mp3_chemin` pointe vers le nouveau fichier). À nettoyer.
- [ ] P104 — Paiement : un échec de paiement (`transaction.payment_failed`) est seulement journalisé, sans email à l'utilisateur. À décider avec les textes de courriel.
- [ ] P105 — Paiement : changement de formule en prorata immédiat (`prorateImmediately()`, et non le défaut de Cashier qui reporte le changement à l’échéance suivante). Vérifier le montant facturé sur un changement réel avant la mise en vente.
- [ ] P106 — Paiement : URL à déclarer dans Paddle : `https://<domaine>/paddle/webhook`, avec les événements subscription.created, subscription.updated, subscription.canceled, subscription.past_due, transaction.completed, transaction.payment_failed.
- [ ] P107 — Paiement : migrations non lancées sur la base PostgreSQL du poste (service arrêté). Elles ont tourné sur SQLite, dans les tests. À lancer sur la recette.
- [ ] P108 — Upgrade : le retour après paiement n'est vérifié qu'avec le sandbox Paddle réel (P102). À vérifier : Paddle renvoie bien à l'écran d'origine (`retour`), et le lien repris se lance au clic une fois le plan Pro reçu par webhook.
- [ ] P109 — Upgrade : les cinq déclencheurs n'ont pas été regardés dans un navigateur. Les tests vérifient le HTML, les liens et les événements ; l'ouverture des fenêtres et le rendu Signal restent à contrôler (adresses dans le compte rendu de la session).
- [ ] P110 — Upgrade : le Pro inclut 30 crédits IA par mois (`VOXIA_PRO_AI_CREDITS_PER_MONTH`), alors que le cahier (§5) dit « encore à fixer ». La fenêtre n'affiche pas le nombre. À confirmer avant la mise en vente.
- [ ] P111 — Upgrade : la section Tarifs de la page d'accueil (maquette) n'est pas reprise ici (étape 5). Les fenêtres présentent les plans avec les valeurs de la config. Les libellés « Historique illimité et recherche » et « Crédits IA pour l'analyse de script » sont fixes dans `lang/*/upgrade.php`.
- [ ] P112 — Back-office : Filament 4.16.1 installé, pas 5.11.1. Filament 5 exige Livewire 4 et le projet est sur Livewire 3 (CLAUDE.md §3). À trancher : rester sur Filament 4, ou monter Livewire 4 (touche les trois composants Livewire existants) puis passer à Filament 5.
- [ ] P113 — Back-office : aucune vérification dans un navigateur (mise en page sombre, accent, modales d'action, filtres). Les tests vérifient le contenu HTML et les actions, pas le rendu. À faire sur le poste de développement avec la base lancée.
- [ ] P114 — Back-office : le pipeline ne débite jamais un traitement en échec. Le bouton « Rembourser » n'apparaît donc que s'il existe une consommation active pour ce traitement (cas rare ou correction manuelle). À confirmer que c'est bien le besoin visé.
- [ ] P115 — Back-office : migration `journal_administration` non lancée sur PostgreSQL (port 5433 refusé pendant cette session). Elle tourne sur SQLite dans les tests. À lancer avec P107 sur la recette.
- [ ] P116 — Back-office : l'alerte suppose le planificateur (cron ou `php artisan schedule:work`) et une file en marche (Horizon) pour l'envoi de l'email. Rien ne le vérifie en production.
- [ ] P117 — Back-office : deux valeurs fixées ici, non prévues par le cahier : délai de 60 minutes entre deux alertes (`voxia.administration.alerte_echecs.delai_minutes`) et motif obligatoire pour un remboursement. À valider.
- [ ] P118 — Back-office : conversions Gratuit vers payant = comptes dont le premier achat (abonnement ou Pack) tombe dans les 30 derniers jours. Pas de taux de conversion (pas de dénominateur défini par le cahier). À confirmer.
- [ ] P119 — Back-office : la consommation affichée tient compte des ajustements (la limite inclut l'ajout accordé). À valider avec le support, car le compteur du client ne change pas de sens entre l'application et le back-office.
- [ ] P120 — Back-office : le coût moyen par heure de vidéo est calculé sur `transcriptions.cout_estime_usd` des transcriptions terminées des 30 derniers jours. Fenêtre et périmètre (coût de l'analyse de script exclu) à confirmer.
- [ ] P121 — Page d'accueil : « Quelles langues sont reconnues ? » et la fiche technique reprennent les six langues de `voxia.langues_forcables` (fr, en, es, de, it, pt, validées en P61). À confirmer que c'est bien la liste à annoncer comme reconnue.
- [ ] P122 — Page d'accueil : durée de conservation affichée « 30 jours [À CONFIRMER] » pour les transcriptions du Gratuit et pour les MP3 (lié à P2). Marqueur à retirer une fois tranchée.
- [ ] P123 — Pages Confidentialité, Conditions et Contact : texte juridique et adresse de contact à fournir. En attendant, les pages affichent « [À CONFIRMER] ».
- [x] P124 — Page d'accueil : `APP_LOCALE=en` ne décide plus de la langue des visiteurs. La langue vient de l'adresse, du compte, du cookie ou du navigateur ; `APP_LOCALE` ne sert plus que de repli technique. *(Résolu avec la bilinguisme, voir « Langues ».)*
- [ ] P125 — Page d'accueil : les boutons de l'aperçu produit (Copier, .txt, .srt, .vtt, .mp3) sont décoratifs. Les garder ainsi, ou les rendre fonctionnels sur une transcription d'exemple ?
- [ ] P126 — Langues : le back-office Filament reste en français (une vingtaine de libellés déclarés dans `app/Filament`, plus les notifications, widgets, colonnes, filtres et actions). Non traduit dans ce lot : à faire avec `lang/fr` et `lang/en` (`administration.*`). Interface interne, mais CLAUDE.md §1 la place dans l'obligation bilingue.
- [ ] P127 — Langues : hors périmètre, en français et non visibles par les visiteurs : descriptions et messages des commandes Artisan (`app/Console`), journaux (`Log::`), messages d'exception internes, prompt d'analyse de script, page `/styleguide` (locale seulement), message du webhook (403). À garder ainsi, ou à traduire ?
- [ ] P128 — Langues : `processing_logs.message` (« piste audio téléchargée », etc.) est en français et s'affiche dans le back-office. Lié à P126.
- [ ] P129 — Langues : la durée (« 12 h 40 », « 40 min ») garde ses unités `h` et `min`, identiques dans les deux langues. Acceptable, ou à traduire ?
- [ ] P130 — Langues : trois variantes anglaises du héros « Chaque vidéo contient un texte. Voxia te l'écrit. » ont été proposées (voir le compte rendu) ; la variante A est appliquée. À confirmer.
- [ ] P131 — Langues : sélecteur, cookie et hreflang vérifiés par les tests HTTP seulement, pas dans un navigateur ni avec PostgreSQL (port 5433 : voir P107). La migration `locale` nullable n'est pas lancée sur PostgreSQL.
- [ ] P132 — Juridique : CGU et politique de confidentialité sont des brouillons (bandeau en tête) à faire relire par un juriste avant lancement. À fournir : forme juridique d'EVANOE (non précisée), adresse email de contact (`VOXIA_CONTACT_EMAIL`, vide tant qu'elle n'est pas donnée : le formulaire et l'adresse ne s'affichent pas), hébergeur, stockage des fichiers, prestataire d'emails, proxys de téléchargement, clauses contractuelles pour les transferts vers les États-Unis, plafond de responsabilité, droit applicable. Prestataire de paiement : Paddle est celui du code, le cahier §8 le donne « à décider ». Tutoiement dans les textes juridiques : à confirmer avec le juriste.
- [ ] P133 — Contact : protection anti-spam sans CAPTCHA (champ piège, délai, limite par IP). Si des messages de robots passent, ajouter un service de vérification : à choisir, il s'agit d'un service tiers.
- [ ] P134 — Export : durée du lien (24 h) et conservation du ZIP (24 h) sont des valeurs proposées (`VOXIA_EXPORT_LIEN_MINUTES`, `VOXIA_EXPORT_CONSERVATION_HEURES`). Le ZIP ne contient pas les journaux techniques de traitement (coûts internes) : à confirmer comme donnée personnelle à exporter.
- [ ] P135 — Tarifs : les crédits IA du Pro s'affichent « inclus, volume à confirmer » (lié à P110).
- [ ] P136 — Pages publiques : mise en page non vérifiée dans un navigateur. Le tableau des tarifs défile dans son cadre sous 640 px (pas la page entière) : à vérifier à 375 px, avec la règle « aucun défilement horizontal de la page ».
- [ ] P137 — Langues : `lang/fr/courriels.php` définit deux fois la clé `rappel_suppression` (le second bloc, avec « 3 jours » écrit en dur, remplace le premier, paramétré). À corriger en gardant la version paramétrée.
- [ ] P138 — Refonte « Nocturne » : icônes Tabler ou Lucide (trait 1,6 à 1,8 px) à intégrer en SVG via un composant `<x-icone>`. Non fait : aucune bibliothèque d'icônes n'est installée.
- [ ] P139 — Refonte « Nocturne » : bascule clair et sombre dans les réglages du compte. Sombre par défaut ; vérifier qu'aucun `data-theme` n'est posé en dur sur `<html>`.
- [ ] P140 — Refonte « Nocturne » : aucune vérification visuelle dans un navigateur (desktop, 320 et 375 px, animations coupées, thème clair et sombre). À faire avant de fusionner.
- [ ] P141 — Refonte « Nocturne » : les PDF d'export (`RendeurPdf`, `ExporteurPdf`, `resources/views/exports/*.blade.php`) utilisent encore Geist et Geist Mono (`resources/fonts/`). mPDF exige des TTF : Onest et JetBrains Mono ne sont fournis qu'en woff2 par fontsource. Choix à trancher : fournir des TTF, ou garder Geist pour les PDF.
- [ ] P142 — Refonte « Nocturne » : la landing actuelle garde son bandeau défilant et la frappe de l'aperçu, que le handoff demande de retirer. Composants et landing à reprendre (étape 2).
- [ ] P143 — Refonte « Nocturne » : la liste de l'historique (miniatures, pagination « Page x sur y ») et l'en-tête du lecteur de transcription (badge « Terminé » et métadonnées au-dessus du lecteur) ne sont pas encore alignés sur leurs maquettes. Le fonctionnel est en place ; seul le rendu manque.
- [ ] P144 — Connexion et inscription : la maquette montre un bouton « Continuer avec Facebook ». Aucun fournisseur Facebook n'est dans la stack ni dans le cahier : non implémenté. À décider (ajouter le fournisseur, ou retirer le bouton de la maquette). En attendant, le bouton est affiché avec un lien « # » (connexion et inscription) : un bouton qui ne mène nulle part sur un écran de connexion est à trancher vite.
- [x] P145 — Réglages : « Packs et factures » liste désormais les factures du fournisseur de paiement (même source que la page Abonnement), comme la maquette « Compte ». *(ReglagesTest, EcransComptesTest ; rendu à vérifier dans un navigateur, P140.)*
- [ ] P146 — Thème : l'espace applicatif était forcé en clair (`data-theme="light"`). Il est passé en sombre, comme la charte et les maquettes. La bascule clair et sombre reste à décider (P139).
- [ ] P147 — Connexion, inscription, historique et pied de page : aucune vérification dans un navigateur (bureau, 375 px, thème clair et sombre, connexion Google désactivée). Les tests vérifient le rendu HTML et les textes, pas la mise en page.
- [ ] P148 — Pied de page du back-office : les classes Filament (`gray-*`, `primary-400`) sont en dur, car le thème `/admin` ne charge pas les tokens Voxia. À revoir si le thème Filament change.
