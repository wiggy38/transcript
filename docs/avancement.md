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

- [ ] Un lien public valide de 10 minutes donne une transcription sans autre action que le lancement.
- [ ] Une vidéo privée, supprimée, bloquée dans la région ou un live en cours affiche un message clair et ne décompte rien. *(Partiel : messages et motifs faits et testés ; le décompte de quota reste à écrire.)*
- [ ] Un échec technique ne décompte rien du quota. *(Partiel : aucune tentative sur erreur définitive, dossier nettoyé ; le décompte reste à écrire.)*
- [ ] Un lien déjà transcrit par le même utilisateur renvoie vers la transcription existante.

### F2 — Transcription horodatée avec ponctuation propre

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
- [ ] P12 — Règle de regroupement en paragraphes (taille cible en mots ou pause maximale). Les valeurs iront dans `config/voxia.php`. À proposer quand le service de regroupement sera écrit.
- [ ] P13 — Recherche sans accents ni racinisation : la configuration `simple` ne reconnaît ni « école » pour « ecole », ni les formes conjuguées. Pistes : configuration `french` / `english` selon la langue détectée, ou extension `unaccent`. À trancher avec des données réelles.
- [ ] P14 — Statuts de l'analyse de script (`en_attente`, `analyse`, `termine`, `echec`) : proposés par le modèle, absents du cahier. À valider.
- [ ] P15 — Langue de l'interface par défaut (`fr` dans le modèle, `APP_LOCALE=en` dans `.env`). À confirmer avec P5.
- [ ] P16 — Essai réel de F1 non fait : yt-dlp absent de la machine et téléchargement refusé. Reste à lancer `voxia:ytdlp-update` puis une lecture de métadonnées sur un lien YouTube public et un lien TikTok public. Les liens de test n'ont pas été fournis (placeholders restés dans la demande).
- [ ] P17 — Fixtures yt-dlp écrites à partir des messages connus, pas enregistrées. À remplacer par de vraies sorties (privée, supprimée, région, live).
- [ ] P18 — Vidéos avec restriction d'âge ou connexion requise : classées en erreur technique (aucun décompte). Faut-il un motif dédié ?
- [ ] P19 — Erreurs génériques « Video unavailable » ou « not available » : classées en erreur technique par prudence. Affiner quand une vraie sortie sera enregistrée.
- [ ] P20 — Formats hors liste (MKV, OGG, AAC brut) refusés par contenu avec `format_non_supporte`. Le 3GP passe avec la famille MP4, faute de moyen fiable de le distinguer. Le cahier ne dit rien de ces formats ; à confirmer.
- [ ] P21 — Nom de fichier : « Partie 1/2 » devient « Partie 12 » (caractère retiré, pas remplacé). Le cahier demande de retirer ; une alternative serait un tiret. À confirmer.
