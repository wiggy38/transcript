# Avancement Voxia

Source : `docs/cahier-des-charges.md` (3 novembre 2026).
Mise à jour à la fin de chaque tâche (voir `CLAUDE.md` §6).

## 1. Planning (cahier §9)

- [ ] Étape 1 — Chaîne de traitement : lien, téléchargement, MP3, transcription, en ligne de commande (2 à 3 semaines)
- [ ] Étape 2 — Comptes, historique et affichage de la transcription (F2, F5) (2 semaines) *(Comptes et F5 faits, voir §2 ; F2 partiel : lecture faite, exports à l'étape 3.)*
- [ ] Étape 3 — Exports (F4), lecteur synchronisé et analyse de script (F7) (2 à 3 semaines)
- [ ] Étape 4 — Plans, quotas, paiement et back-office (F6) (2 semaines)
- [ ] Étape 5 — Page d'accueil, page des tarifs, CGU, puis bêta privée avec 20 à 50 créateurs et marketeurs (2 semaines)

## 2. Critères d'acceptation

### F1 — Transcription depuis un lien ou un fichier

- [ ] Un lien public valide de 10 minutes donne une transcription sans autre action que le lancement. *(Partiel : chaîne complète et écran de lancement testés avec les faux services ; aucun lien réel essayé, yt-dlp absent, voir P16.)*
- [ ] Une vidéo privée, supprimée, bloquée dans la région ou un live en cours affiche un message clair et ne décompte rien. *(Partiel : messages et motifs affichés sur l'écran avant tout lancement, testés ; arrêt avant téléchargement pour le live ; le décompte de quota reste à écrire.)*
- [ ] Un échec technique ne décompte rien du quota. *(Partiel : échec à chaque étape testé, fichiers temporaires supprimés, aucune ligne de consommation écrite ; le décompte lui-même reste à écrire, voir le point d'accroche `TranscriptionCompleted`.)*
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

- [ ] Aucun critère d'acceptation défini dans le cahier. Voir point ouvert P0.

### F7 — Analyse de la structure d'un script publicitaire

- [ ] Sur 20 pubs découpées à la main, les limites des parties tombent à 2 secondes près dans 80 % des cas (cible à valider).
- [ ] Les parties ne se chevauchent pas et suivent l'ordre de la vidéo.
- [ ] L'analyse d'une pub de 60 secondes s'affiche en moins de 15 secondes.
- [ ] Un clic sur une partie lance la lecture à son début.

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

## 3. Décisions

- Comptes : Fortify pour l'authentification (vues Signal dans `resources/views/auth`), sans double authentification ni passkeys (hors MVP, désactivés dans `config/fortify.php`). Socialite pour Google.
- Lien en attente : colonne `users.lien_en_attente`. Tant que le visiteur est anonyme, le lien est gardé en session (`App\Services\Comptes\LienEnAttente`) ; il est reporté sur le compte à la connexion ou à l'inscription (événement `Login`).
- Lancement : à l'arrivée dans l'application (`/tableau-de-bord`), une fois l'email vérifié. Le bouton « Transcrire » est déjà la demande de lancement, donc la reprise ne demande pas de second clic.
- Lancement depuis le web (`POST /transcriptions`) : seules les adresses `http(s)` sont acceptées. `LancerTranscription` sait aussi lire un fichier local : ce chemin ne doit jamais venir d'un formulaire.
- Limite par IP : compte les comptes au plan Gratuit créés depuis l'IP (`users.inscription_ip`). Les comptes Pro ne comptent pas. Le message est volontairement neutre.
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
- `QuotaService` : appelé par `LancerTranscription` avant chaque création (lien ou fichier), jamais pour une transcription déjà existante. Il autorise tout pour l'instant ; le décompte reste à écrire (F6).
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
  - Événements : `TranscriptionCompleted` (point d'accroche de la consommation, à écrire avec F6) et `TranscriptionFailed(motif)` (point d'accroche des notifications). Aucune ligne `usage_records` n'est écrite par la chaîne.
- Files : `ProcessTranscription` va sur `high` pour le plan Pro, `default` pour Gratuit et Pack (`voxia.traitement.plans_prioritaires`). Horizon sert les files dans l'ordre listé, avec `balance => false`.
- Plan de l'utilisateur : colonne `users.plan` (défaut `free`), enum `App\Enums\Plan`. Ajoutée ici car le routage des files en dépend ; la logique de plan et de quota reste à faire (F6).
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

## 4. Points ouverts

- [ ] P0 — Critères d'acceptation de F6 (offre gratuite) : à rédiger, le cahier n'en fournit pas.
- [ ] P1 — L'extraction MP3 seule, sans transcription, décompte-t-elle une vidéo en gratuit ?
- [ ] P2 — Durée de conservation des MP3 et des fichiers importés (proposition : 30 jours). Branchée dans `voxia.retention.media_days` (30 jours par défaut) ; la valeur reste à valider.
- [ ] P3 — Taille maximale d'un fichier importé (proposition : 2 Go).
- [ ] P4 — Prestataire de paiement et structure juridique qui encaisse (Stripe ou Paddle ; Stripe semble fermé aux entreprises du Burkina Faso, à vérifier).
- [ ] P5 — Langues de l'interface au lancement (proposition : anglais et français).
- [ ] P6 — Autoriser un premier essai sans compte depuis la page d'accueil ?
- [ ] P7 — Nombre de crédits IA inclus chaque mois dans le Pro ; une analyse d'essai en Gratuit pour déclencher l'upgrade ?
- [ ] P8 — Fournisseur du modèle de langage utilisé pour F7.
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
- [ ] P75 — Droits par plan : tout le monde est Gratuit tant que `VOXIA_PLAN_REEL` est faux. Le test des plans Pro et Pack se fait avec ce drapeau ; l'étape 4 le remplacera.
- [ ] P76 — Historique : écrans non vérifiés dans un navigateur à 375 px (mêmes statuts que P44 et P58), dont le bouton de confirmation de suppression.
- [ ] P77 — Pagination : précédente et suivante seulement (pas de numéros de page) pour rester lisible au téléphone. À confirmer avec la maquette.
- [ ] P78 — Import réel des SRT et VTT dans YouTube Studio et CapCut : à faire à la main avec `storage/app/exports-test/exemple.srt` et `exemple.vtt`. Le validateur de règles ne remplace pas ce test.
- [ ] P79 — Export PDF pendant la requête web : contraire à la règle « tout passe par la file » (CLAUDE.md §5). Mesuré sur le poste de développement, pour 28 848 mots (3 heures) : PDF 14,5 s, SRT 0,45 s, TXT moins de 0,02 s. Décision à prendre : passer le PDF par la file avec téléchargement différé, ou garder la requête si la mesure le permet.
- [ ] P80 — Écritures sans couverture dans le PDF : arabe et CJK. La mise en forme de Noto Sans Arabic échoue dans mPDF (table GPOS de type 5 non prise en charge). TXT, SRT et VTT les gèrent. Choix à faire : police CJK plus lourde, ou moteur PDF différent.
- [ ] P81 — Bouton MP3 de la barre d'export : toujours inactif. Le téléchargement du MP3 relève de F3 et n'est pas branché ici.
