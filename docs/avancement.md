# Avancement Voxia

Source : `docs/cahier-des-charges.md` (3 novembre 2026).
Mise à jour à la fin de chaque tâche (voir `CLAUDE.md` §6).

## 1. Planning (cahier §9)

- [ ] Étape 1 — Chaîne de traitement : lien, téléchargement, MP3, transcription, en ligne de commande (2 à 3 semaines)
- [ ] Étape 2 — Comptes, historique et affichage de la transcription (F2, F5) (2 semaines)
- [ ] Étape 3 — Exports (F4), lecteur synchronisé et analyse de script (F7) (2 à 3 semaines)
- [ ] Étape 4 — Plans, quotas, paiement et back-office (F6) (2 semaines)
- [ ] Étape 5 — Page d'accueil, page des tarifs, CGU, puis bêta privée avec 20 à 50 créateurs et marketeurs (2 semaines)

## 2. Critères d'acceptation

### F1 — Transcription depuis un lien ou un fichier

- [ ] Un lien public valide de 10 minutes donne une transcription sans autre action que le lancement. *(Partiel : chaîne complète testée avec les faux services ; aucun lien réel essayé, yt-dlp absent, voir P16.)*
- [ ] Une vidéo privée, supprimée, bloquée dans la région ou un live en cours affiche un message clair et ne décompte rien. *(Partiel : messages et motifs faits et testés, arrêt avant téléchargement pour le live ; le décompte de quota reste à écrire.)*
- [ ] Un échec technique ne décompte rien du quota. *(Partiel : échec à chaque étape testé, fichiers temporaires supprimés, aucune ligne de consommation écrite ; le décompte lui-même reste à écrire, voir le point d'accroche `TranscriptionCompleted`.)*
- [ ] Un lien déjà transcrit par le même utilisateur renvoie vers la transcription existante. *(Partiel : vérifié pour YouTube et TikTok avec identifiant connu. Un lien court `vm.tiktok.com` n'a pas d'identifiant avant résolution, donc pas de contrôle à la création, voir P32.)*

### F2 — Transcription horodatée avec ponctuation propre

*(Partiel : moteur et regroupement en paragraphes faits et testés ; affichage, lecteur, recherche et copie restent à faire.)*

- [ ] Le clic sur un horodatage positionne la lecture à 1 seconde près.
- [ ] Une transcription de 3 heures s'affiche et reste fluide au défilement.

### F3 — Extraction de l'audio en MP3

- [ ] Le MP3 téléchargé a la même durée que la vidéo source, à 1 seconde près. *(Partiel : `AudioExtractor` testé sur une vidéo et un bip générés, 1 s près, débit 192 kbit/s vérifié ; reste à le brancher sur le téléchargement réel, étape 1.)*
- [x] Le nom du fichier ne contient que des caractères valides sous Windows, macOS et Linux. *(Service `NomFichierAudio` testé sur 10 titres piégés.)*

### F4 — Export TXT, PDF, SRT, VTT

- [ ] Les fichiers SRT et VTT s'importent sans erreur dans YouTube Studio et CapCut.
- [ ] Les accents et caractères non latins s'affichent correctement dans chaque format.

### F5 — Historique et recherche

- [ ] Une recherche sur 500 transcriptions répond en moins d'une seconde.
- [ ] Une transcription supprimée disparaît de la liste et de la recherche.

### F6 — Offre gratuite limitée

- [ ] Aucun critère d'acceptation défini dans le cahier. Voir point ouvert P0.

### F7 — Analyse de la structure d'un script publicitaire

- [ ] Sur 20 pubs découpées à la main, les limites des parties tombent à 2 secondes près dans 80 % des cas (cible à valider).
- [ ] Les parties ne se chevauchent pas et suivent l'ordre de la vidéo.
- [ ] L'analyse d'une pub de 60 secondes s'affiche en moins de 15 secondes.
- [ ] Un clic sur une partie lance la lecture à son début.

## 3. Décisions

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

## 4. Points ouverts

- [ ] P0 — Critères d'acceptation de F6 (offre gratuite) : à rédiger, le cahier n'en fournit pas.
- [ ] P1 — L'extraction MP3 seule, sans transcription, décompte-t-elle une vidéo en gratuit ?
- [ ] P2 — Durée de conservation des MP3 et des fichiers importés (proposition : 30 jours).
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
- [ ] P34 — Durée maximale sur le plan gratuit : la chaîne applique `max_video_seconds` (10 min en Gratuit). Confirmer que refuser une vidéo de 15 min en Gratuit est bien le comportement voulu (cahier F1 parle d'un email au-delà de 15 min, pas d'un refus).
- [ ] P35 — Noms de files codés en dur dans `config/horizon.php` (`high`, `default`), à garder synchronisés avec `voxia.traitement.files`.
- [ ] P36 — Aucun essai réel : yt-dlp absent et pas de lien de test fourni (même point que P16). La chaîne réelle (yt-dlp, FFmpeg, ElevenLabs) n'a été exercée que par ses propres tests existants et les faux services.
