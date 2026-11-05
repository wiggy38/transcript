# Voxia — État des lieux avant la V2

5 novembre 2026 — branche `feat/mvp`, lecture du code uniquement (aucun fichier de code modifié).

Sources : `docs/cahier-des-charges.md` (source de vérité), `docs/voxia-offre.md`, `docs/voxia-personas.md`, `docs/mesures-etape-1.md`, `docs/avancement.md`, `CLAUDE.md`, et le code de `app/`, `config/`, `routes/`, `resources/`, `lang/`, `tests/`.

Les chemins cités dans la demande qui n'existent pas sont signalés en §10 (`docs/v2/decisions.md`, `docs/voxia-cahier-des-charges-mvp.md`). Ce dossier `docs/v2/` vient d'être créé par cette étape.

---

## 1. Structure

### 1.1 Stack réelle

| Domaine | Choix constaté | Source |
| --- | --- | --- |
| Framework | Laravel 13.40, PHP ^8.3 (PHP 8.4.15 en local) | `composer.json`, `composer.lock` |
| Rendu | Blade + Livewire 3 (Alpine fourni par Livewire). Pas d'Inertia, pas de React ni Vue | `app/Livewire/`, `resources/js/app.js` |
| Front | Vite 8, Tailwind CSS v4 (`@tailwindcss/vite`), polices auto-hébergées (fontsource) | `vite.config.js`, `package.json` |
| Admin | Filament 4 sur `/admin`, réservé aux comptes `is_admin` | `app/Providers/Filament/AdminPanelProvider.php` |
| Authentification | Laravel Fortify. Google (Socialite) et Facebook, désactivés par défaut (`VOXIA_GOOGLE_LOGIN`, `VOXIA_FACEBOOK_LOGIN`) | `config/voxia.php` › `comptes` |
| Paiement | Laravel Cashier **Paddle** 2.8. Stripe n'apparaît nulle part dans le code | `config/cashier.php`, `app/Services/Paiement/PaddleBillingProvider.php` |
| Files | Redis + Horizon (Linux/WSL). Sous Windows, `queue:work` (Horizon ne démarre pas) | `docs/mesures-etape-1.md` §4 |
| PDF | mPDF 8.3, vues Blade dans `resources/views/exports/` | `app/Services/Exports/RendeurPdf.php` |
| Base | PostgreSQL en développement et en production (recherche plein texte, index GIN). SQLite en mémoire pour les tests | `phpunit.xml` |
| Tests | Pest 4 (PHPUnit 12) | `composer.json` |

### 1.2 Modèles (`app/Models/`)

| Modèle | Table | Rôle |
| --- | --- | --- |
| `User` | `users` | Compte. Colonnes ajoutées : `plan`, `locale`, `is_admin`, `abonnement_debut_at` |
| `Transcription` | `transcriptions` | Une vidéo ou un fichier transcrit. Porte le statut, le motif d'échec, les dates de chaque étape, `est_apercu`, `mp3_chemin` |
| `TranscriptionWord` | `transcription_words` | Un mot horodaté (sans `created_at`, écrit en lot) |
| `TranscriptionParagraph` | `transcription_paragraphs` | Un paragraphe (début, fin, texte, `config_recherche`) |
| `ScriptAnalysis` | `script_analyses` | Une analyse F7 par transcription (unique) |
| `ScriptPart` | `script_parts` | Une des cinq parties d'une analyse |
| `UsageRecord` | `usage_records` | Journal des décomptes : vidéos, secondes, crédits IA |
| `PackAchat` | `pack_achats` | Un Pack acheté, avec ses secondes restantes et sa date d'expiration |
| `ProcessingLog` | `processing_logs` | Une ligne par étape de traitement (durée, succès, message, coût) |
| `EvenementPaiement` | `evenements_paiement` | Webhook reçu, unique par `event_id` |
| `JournalAdministration` | `journal_administration` | Actions du back-office |

Cashier ajoute `customers`, `subscriptions`, `subscription_items`, `transactions` (migrations 2019, non modifiées).

### 1.3 Migrations (`database/migrations/`)

Migrations Laravel de base (users, cache, jobs), puis, de `2026_11_03_201858` à `2026_11_04_300000` (25 au total) :

- transcriptions, transcription_words, transcription_paragraphs, script_analyses, script_parts, usage_records, processing_logs
- locale et `is_admin` sur users, plan sur users, colonnes de comptes, recherche par langue et rappel de suppression
- quotas et packs, événements de paiement, transaction Paddle sur les packs, journal d'administration, locale facultative

### 1.4 Jobs (`app/Jobs/`)

| Job | File | Rôle | Tentatives / délai |
| --- | --- | --- | --- |
| `ProcessTranscription` | standard ou `high` (Pro) | Chaîne complète d'une vidéo | 1 tentative, délai total 7 200 s, `failed()` passe en échec |
| `AnalyserScript` | selon le plan | Analyse F7 | 1 tentative, délai 90 s |
| `RecupererApercu` | — | Titre, durée et miniature d'un lien, pour l'écran de lancement | — |
| `ExporterDonneesCompte` | — | ZIP de toutes les données du compte, envoyé par lien signé | — |
| `ProcesserEvenementPaiement` | — | Traitement d'un webhook Paddle | — |
| `SupprimerFichiersTranscriptions` | — | Suppression des fichiers d'une transcription | — |

### 1.5 Services (`app/Services/`), par domaine

- **Traitement** : `ChaineTranscription` (la chaîne), `LancerTranscription` (création, mise en file, reprise après paiement), `BilanEtape`
- **Video** : `YtDlpVideoFetcher` (yt-dlp), `ReconnaissanceLien`, `ApercuLien`, `TraducteurErreurYtdlp`, `FauxVideoFetcher`
- **Audio** : `FfmpegAudioExtractor` (FFmpeg), `NomFichierAudio`, `FauxAudioExtractor`
- **Transcription** : `ElevenLabsScribeEngine`, `ReponseElevenLabs`, `ParagraphBuilder`, `CoutTranscription`, `FakeTranscriptionEngine`
- **Analyse (F7)** : `LancerAnalyseScript`, `ServiceAnalyseScript`, `ClaudeScriptAnalyzer`, `ValidateurAnalyse`, `FakeScriptAnalyzer`
- **Quota** : `QuotaService`, `AchatPack`
- **Plans** : `PlanFeatures` (droits par plan), `PresentationTarifs`
- **Paiement** : `PaddleBillingProvider`, `FauxBillingProvider`, `RecevoirEvenementPaiement`, `TraiterEvenementPaiement`, `SynchroniserPlan`
- **Exports** : `Exporteurs` (sélecteur), `ExporteurTxt`, `ExporteurPdf`, `ExporteurSrt`, `ExporteurVtt`, `ExporteurAnalyse`, `GenerateurSousTitres`, `RendeurPdf`
- **Historique et recherche** : `RechercheTranscriptions`, `SuppressionTranscription`
- **Comptes** : `LienEnAttente`, `LimiteComptesGratuits`
- **Conservation** : `Conservation` (purge, rappel avant suppression)
- **Téléversement** : `Televersement` (fichiers en morceaux)
- **Administration** : `AjustementQuota`, `RemboursementTranscription`, `ConsommationUtilisateur`, `IndicateursAdministration`, `AlerteEchecsTelechargement`

### 1.6 Interfaces derrière `app/Contracts/` (liaison dans `AppServiceProvider`)

| Interface | Implémentation réelle | Fausse implémentation (tests) |
| --- | --- | --- |
| `VideoFetcher` | `YtDlpVideoFetcher` | `FauxVideoFetcher` |
| `AudioExtractor` | `FfmpegAudioExtractor` | `FauxAudioExtractor` |
| `TranscriptionEngine` | `ElevenLabsScribeEngine` | `FakeTranscriptionEngine` |
| `ScriptAnalyzer` | `ClaudeScriptAnalyzer` | `FakeScriptAnalyzer` |
| `BillingProvider` | `PaddleBillingProvider` | `FauxBillingProvider` |

### 1.7 Contrôleurs et routes (`routes/web.php`)

- **Public, par langue** (`/{locale}`, `en` ou `fr`) : accueil, `/tarifs`, `/questions`, `/conditions`, `/confidentialite`, `/contact` (GET et POST). La racine `/` redirige vers la langue du visiteur.
- **Lancement** : `POST /lien` (`LienController`), écran Livewire `NouvelleTranscription`.
- **Compte** (middleware `auth`) : tableau de bord, transcription (`/transcriptions/{id}`), historique (`/historique`), suppression (GET de confirmation, DELETE), audio (`/audio`), MP3 et exports en lien signé (`signed`), analyse F7 en export TXT et PDF (lien signé).
- **Téléversement** : `POST /televersements/morceaux` (email vérifié exigé).
- **Réglages** : langue, suppression du compte, demande d'export des données (lien signé reçu par email).
- **Paiement** : `/paiement/{produit}` (email vérifié), `/abonnement` (changer de formule, résilier, reprendre, facture).
- **Webhook** : `POST /paddle/webhook` (signature vérifiée, sans session ni CSRF).
- **Local seulement** : `/styleguide`.

Contrôleurs : `AccueilController`, `ContactController`, `LangueController`, `Comptes/*` (11), `Paiement/*` (3), middleware `DefinirLangue`.

### 1.8 Vues et composants

- `resources/views/` : `app/` (tableau de bord, historique, réglages), `auth/`, `components/` (boutons, champs, lecteur, onde, progression, carte de plan), `emails/`, `exports/` (PDF), `layouts/`, `livewire/` (3 composants), `pages/` (tarifs, questions, juridique), `filament/`.
- Livewire 3 : `NouvelleTranscription`, `SuiviTranscription` (interrogation toutes les 3 s tant que la transcription est en cours), `AnalyseScript`.
- JavaScript : `televersement.js` (fichiers en morceaux) et `lecteur-transcription.js` (lecteur synchronisé), enregistrés dans Alpine (`resources/js/app.js`).
- Styles : `resources/css/app.css`, `voxia-tokens.css` (charte « Nocturne »), `filament/admin/theme.css`.

---

## 2. Chaîne de traitement d'une vidéo

Orchestrée par `ChaineTranscription::executer()`. La tâche `ProcessTranscription` la lance dans la file, jamais dans la requête web.

**Lancement** (`LancerTranscription`) : pour un lien, si le même utilisateur a déjà transcrit la même vidéo (`source` + `id_video`), la transcription existante est renvoyée. Sinon, le quota est vérifié dans une transaction avec verrou sur le compte, la ligne est créée au statut `en_attente`, puis la tâche est mise en file (`high` pour le Pro, `default` sinon).

**Étapes**, chacune écrite dans `processing_logs` avec sa durée :

| # | Étape (`EtapeTraitement`) | Ce qui se passe | Statut affiché |
| --- | --- | --- | --- |
| 1 | `metadonnees` | Pour un lien : `yt-dlp` lit titre, durée, chaîne, miniature. Un live en cours est refusé (`live_en_cours`). La durée est vérifiée contre le plan. Pour un fichier : le nom sert de titre | `telechargement` |
| 2 | (quota) | Pour un lien, `verifierTraitement` contrôle le quota dès que la durée est connue, avant tout téléchargement | |
| 3 | `telechargement` | `yt-dlp` télécharge la piste audio (délai 900 s, 3 tentatives sur erreur technique, proxy `VOXIA_PROXY_URL` facultatif). Un fichier local est pris tel quel | |
| 4 | `extraction_audio` | FFmpeg produit un MP3 à 192 kbit/s, nommé d'après le titre. Pour un fichier, la durée réelle est lue ici et le quota est vérifié à nouveau | |
| 5 | `transcription` | `ElevenLabsScribeEngine` appelle Scribe v2 (`POST /v1/speech-to-text`, `timestamps_granularity=word`, langue forcée facultative). 3 tentatives, 2 s entre elles, délai 900 s | `transcription` |
| 6 | `paragraphes` | `ParagraphBuilder` regroupe les mots : coupure après une pause de 1 200 ms, après une fin de phrase si le paragraphe a au moins 20 mots, et à 60 mots au plus | |
| 7 | `enregistrement` | Dans une transaction : écriture des mots et paragraphes, décompte du quota (`consommerTranscription`), passage à `termine`, date d'achèvement. Un échec ici n'écrit rien | `termine` |

**Statuts** (`StatutTranscription`) : `en_attente`, `telechargement`, `transcription`, `termine`, `echec`. La progression est affichée à partir de ces valeurs et des dates de chaque étape.

**Motifs d'échec** (`MotifEchec`) : `lien_invalide`, `video_privee`, `video_supprimee`, `bloquee_region`, `live_en_cours`, `trop_longue`, `quota_epuise`, `sans_audio`, `format_non_supporte`, `erreur_technique`. Une `ErreurVideo` donne son motif. Toute autre exception donne `erreur_technique` et part dans `report()`.

**Règle de décompte** : rien n'est décompté avant `enregistrement`. Un échec technique ne décompte donc rien (conforme à la règle CLAUDE.md §5).

**Délai total** : `ProcessTranscription` a un délai de 7 200 s (`voxia.traitement.delai_total_secondes`). Si Laravel l'interrompt, `failed()` passe la transcription en `erreur_technique`. Il n'y a pas de nouvelle tentative au niveau de la tâche.

**Aperçu** : sur le plan Gratuit, une vidéo de plus de 10 minutes est transcrite sur ses 10 premières minutes (`est_apercu = true`). Après paiement, `LancerTranscription::relancerVersionComplete` la relance en version complète sur la même ligne.

**Événements** : `TranscriptionCompleted` et `TranscriptionFailed`. Un email de fin est prévu au-delà de `voxia.notifications.long_video_email_after_seconds` (900 s), par l'écouteur `PrevenirFinVideoLongue`.

**Stockage** :
- MP3 : disque `voxia.stockage.disque` (`local` en dev, un disque compatible S3 en prod). Chemin dans `transcriptions.mp3_chemin`.
- Fichiers importés : `storage/app/televersements/` (un sous-dossier par utilisateur, morceaux de 1 Mo, 2 Go maximum).
- Fichiers temporaires : `storage/app/temp/`, un dossier par tâche, supprimé dans un `finally` (succès comme échec).
- Exports (TXT, PDF, SRT, VTT) : générés à la demande, non stockés.

**Conservation** (planifiée) : `voxia:purger-historique` (30 jours en Gratuit), `voxia:rappeler-suppression` (3 jours avant), `voxia:purger-medias` (30 jours pour les MP3 et les fichiers importés), `voxia:purger-exports` (24 h pour les ZIP).

---

## 3. Format exact des segments horodatés en base

Il n'y a pas de table « segments ». Le texte est stocké à deux niveaux, en millisecondes depuis le début du fichier.

**`transcription_words`** (un mot par ligne, écrit en lot, sans `created_at`) :

| Colonne | Type | Contenu |
| --- | --- | --- |
| `transcription_id` | FK, cascade | Transcription parente |
| `position` | entier non signé | Rang du mot, unique par transcription (index unique) |
| `texte` | varchar(255) | Mot tel que renvoyé par Scribe, ponctuation éventuelle incluse |
| `debut_ms` | entier non signé | Début du mot, en ms |
| `fin_ms` | entier non signé | Fin du mot, en ms |

**`transcription_paragraphs`** (calculés à partir des mots) :

| Colonne | Type | Contenu |
| --- | --- | --- |
| `position` | entier non signé | Rang du paragraphe, unique par transcription |
| `debut_ms` | entier non signé | Début du premier mot |
| `fin_ms` | entier non signé | Fin du dernier mot |
| `texte` | text | Mots joints par `TexteMots::joindre`, ponctuation du moteur conservée |
| `config_recherche` | varchar(20), défaut `simple` | Configuration PostgreSQL de recherche (`french`, `english`, `spanish`, `german`, `italian`, `portuguese`, ou `simple`) |

Index GIN sous PostgreSQL uniquement : un index partiel par configuration (`to_tsvector('<config>', texte) WHERE config_recherche = '<config>'`), plus un index `simple` pour les langues sans configuration.

**`transcriptions`** : `duree_secondes` (entier, en secondes), `mp3_duree_secondes`, `langue_detectee` (8 caractères au plus), `langue_forcee`, `nombre_mots`, `cout_estime_usd`.

**`script_parts`** (F7, cinq lignes par analyse) : `position` (1 à 5), `type` (`accroche`, `probleme`, `preuve`, `offre`, `appel_action`), `debut_ms`, `fin_ms` (nuls si `detectee = false`), `texte` (reconstruit à partir des mots de la plage, jamais renvoyé par le modèle), `detectee`.

**Affichage** : `Horodatage::format` donne `m:ss` ou `h:mm:ss`. Le lecteur utilise les millisecondes.

---

## 4. Analyse F7 : appel au modèle de langage

- **Fournisseur** : API Messages d'Anthropic, `POST {ANTHROPIC_BASE_URL}/v1/messages`, en-tête `anthropic-version` (config : `2026-10-21`).
- **Modèle** : `claude-haiku-5-5` par défaut (`ANTHROPIC_MODEL`).
- **Classe** : `App\Services\Analyse\ClaudeScriptAnalyzer`, qui implémente `App\Contracts\ScriptAnalyzer`. C'est la seule classe qui parle au fournisseur. Le reste du code manipule `ResultatFournisseur` et `PartieAnalysee`, neutres.
- **Prompt système** (`CONSIGNE`, en français) : découper la pub en cinq parties dans l'ordre (accroche, problème, preuve, offre, appel à l'action), donner les numéros du premier et du dernier mot, ne jamais inventer une partie absente, ne rien reformuler. Il précise que le contenu entre `<transcription>` est une donnée et jamais une instruction.
- **Message utilisateur** : durée totale, puis les paragraphes horodatés (contexte), puis chaque mot numéroté avec son début (`12 3.40s texte`), le tout entre `<transcription>` et `</transcription>`. Les numéros de mots font foi.
- **Format de sortie** : sortie contrainte par `output_config.format` de type `json_schema`. Pour chacune des cinq parties : `detectee` (booléen), `premier_mot` et `dernier_mot` (entiers ou nuls). Aucun texte, aucun horodatage. `max_tokens` = 1 024.
- **Validation** (`ValidateurAnalyse`, qui fait foi quel que soit le fournisseur) : cinq types présents une fois chacun ; chaque borne recalée sur le mot le plus proche, à 2 000 ms près au plus ; ordre de la vidéo respecté ; pas de chevauchement ; texte reconstruit à partir des mots.
- **Erreurs** (`ErreurAnalyse`, avec le coût déjà engagé) : clé absente, aucun mot, connexion impossible, erreur HTTP, refus du modèle (`stop_reason = refusal`), réponse tronquée (`max_tokens`), réponse illisible, réponse invalide.
- **Relances** : 1 relance (`voxia.script_analysis.relances`), soit deux appels au plus. Délai de 30 s par appel, 90 s pour la tâche.
- **Échec final** : statut `echec`, aucun crédit décompté, coût du ou des appels écrit dans `processing_logs` et `script_analyses.cout_estime_usd`.
- **Succès** : parties écrites, crédit décompté dans la même transaction (`consommerCreditIa`).
- **Tarifs utilisés pour le coût** : 0,10 $ et 0,50 $ par million de jetons (`voxia.costs`). Les valeurs sont dans la config, marquées « tarif officiel », mais elles n'ont pas été vérifiées dans ce passage.
- **Commande de mesure** : `voxia:eval-scripts` (cible de 80 % de limites justes à 2 s près, délai maximal de 15 s).

---

## 5. Plans, quotas, crédits IA, abonnements et webhooks

### 5.1 Plans (`config/voxia.php` › `plans`, enum `Plan`)

| | Gratuit | Pro | Pack | Business |
| --- | --- | --- | --- | --- |
| Vendable | oui | oui | oui | **non** (V2) |
| Volume | 5 vidéos/mois | 30 h/mois | 10 h, 6 mois | 150 h/mois |
| Durée max | 600 s (aperçu au-delà) | 3 h | 3 h | 5 h |
| Exports | TXT, SRT | TXT, PDF, SRT, VTT | TXT, PDF, SRT, VTT | tous |
| Historique | 30 jours | illimité | illimité | illimité |
| Recherche | non | oui | oui | oui |
| Crédits IA / mois | 0 | 30 (décidé le 5 nov. 2026) | 0 | 300 (décidé le 5 nov. 2026) |
| File | `default` | `high` | `default` | — |

Droits centralisés dans `PlanFeatures` ; l'interrupteur `voxia.droits.plan_reel` (défaut vrai) fait que le plan enregistré sur le compte fait foi.

### 5.2 Décompte (`QuotaService`)

- **Gratuit** : une vidéo réussie = 1 vidéo, quel que soit sa durée. Le compteur repart le 1er du mois (UTC).
- **Pro** : durée réelle arrondie à la minute supérieure, décomptée sur le volume mensuel. Le cycle repart à la date anniversaire de l'abonnement (`users.abonnement_debut_at`).
- **Packs** : quand le Pro ne suffit pas, les Packs sont consommés du plus ancien au plus récent, dans la limite de leur validité.
- **Crédits IA** : mensuels, Pro seulement, décomptés à part. Une analyse = 1 crédit (`voxia.script_analysis.credits_per_analysis`).
- **Réservation** : à la création, une transcription en cours compte comme réservée (verrou sur la ligne du compte). Le décompte final se refait sous verrou dans `consommerTranscription`.
- **Relecture** : `resume()` donne le reste affiché dans l'interface.

### 5.3 Abonnements et packs

- **Achat** : `PaiementController` ouvre la formule (`pro_mensuel`, `pro_annuel`, `pack`) ; les identifiants de prix Paddle sont dans `VOXIA_PADDLE_PRICE_*`. Un identifiant vide rend la formule non vendable.
- **Résiliation et changement de formule** : `AbonnementController`. La facturation est confiée à Paddle.
- **Plan réel** : `SynchroniserPlan::pour()` calcule le plan à partir de l'abonnement Pro (actif ou résilié mais encore payé), puis des Packs non expirés, sinon Gratuit. Le résultat est écrit dans `users.plan`, sous verrou.
- **Recalcul quotidien** : `voxia:recalculer-plans` (03:45) ramène chaque compte au plan qui lui reste (fin de période, Pack expiré).

### 5.4 Webhooks Paddle

1. `POST /paddle/webhook` : Cashier vérifie la signature (middleware), puis `WebhookPaddleController` appelle `RecevoirEvenementPaiement::enregistrer()`.
2. Sans `PADDLE_WEBHOOK_SECRET`, la route répond 403.
3. L'événement est enregistré dans `evenements_paiement`, unique sur `event_id` : un doublon ne crée pas de ligne et ne relance rien.
4. `ProcesserEvenementPaiement` est mis en file. `TraiterEvenementPaiement` traite le type : synchronisation de l'abonnement, transaction réglée (achat de Pack), relance d'un aperçu après paiement.
5. Chaque traitement passe par `SynchroniserPlan`.

---

## 6. Exports existants

| Format | Classe | Où c'est généré | Contrôles |
| --- | --- | --- | --- |
| TXT | `ExporteurTxt` | En mémoire, à la demande | Format dans le plan (403 sinon) ; transcription terminée et du compte (404 sinon) |
| PDF | `ExporteurPdf` + `RendeurPdf` (mPDF) | Vue `exports/transcription-pdf.blade.php`, texte échappé avant la vue | Idem ; titre, lien source et date |
| SRT | `ExporteurSrt` + `GenerateurSousTitres` | En mémoire | 42 caractères par ligne, 2 lignes, 1 à 7 s (config) |
| VTT | `ExporteurVtt` + `GenerateurSousTitres` | En mémoire | Idem |
| Analyse F7 (TXT, PDF) | `ExporteurAnalyse` | Vue `exports/analyse-pdf.blade.php` | Lien signé ; accès selon le plan |
| MP3 | `TelechargementMp3Controller` | Lu depuis le disque | Lien signé, 15 minutes |
| Données du compte (ZIP) | `ExporterDonneesCompte` (job) | Préparé par la file, envoyé par email | Lien signé de 24 h |

Le paramètre `horodatages` (défaut vrai) permet de masquer les horodatages en TXT et PDF. Les noms de fichiers passent par `NomFichierAudio::depuisTitre`.

---

## 7. Gestion des langues de l'interface

- **Deux langues** : `en` et `fr` (`voxia.locales`), défaut `en` (`voxia.locale_defaut`).
- **Adresse** : toutes les pages publiques ont un préfixe `/{locale}`, avec balises hreflang. La racine redirige via `App\Support\Langue::resoudre` (adresse, puis compte, puis navigateur).
- **Middleware** `DefinirLangue` pose la langue sur la requête et sur `URL::defaults`. `LangueController` et le réglage `reglages.langue` l'enregistrent sur le compte (`users.locale`, facultatif).
- **Fichiers** : 17 fichiers par langue dans `lang/fr/` et `lang/en/`. Vérification faite pour ce document : mêmes clés dans les deux langues (0 clé manquante, par fichier).
- **Emails et PDF** : traduits eux aussi (`courriels.php`, `exports.php`, `analyse.php`).
- **Distinction à garder** : les langues de l'interface (`en`, `fr`) et les langues de transcription forçables (`fr`, `en`, `es`, `de`, `it`, `pt`, `voxia.langues_forcables`) sont deux listes différentes.

---

## 8. Tests existants et commandes

**Tests** : 64 fichiers `*Test.php` (`tests/Feature/` : Accueil, Administration, Analyse, Audio, Comptes, Exports, Historique, Interface, Langue, Models, Pages, Paiement, Quotas, Traitement, Transcription, Transcriptions, Upgrade, Videos ; `tests/Unit/` : Support). Les fausses implémentations des cinq contrats servent dans les tests : aucun appel réel.

**Résultat de la suite, lancée pour ce document** (`php artisan test`, environ 6 minutes) :

> 590 tests, 579 réussis, 11 ignorés, 1 risqué, 0 échec, 4 227 assertions.

Les 11 ignorés sont les tests de **recherche plein texte**, qui ne tournent que sous PostgreSQL (`->skip(fn () => DB::getDriverName() !== 'pgsql')`). **Ils ne sont donc pas exécutés par la commande par défaut.** Voir §9.

**Commandes utiles** :

| Besoin | Commande |
| --- | --- |
| Tous les tests | `php artisan test` |
| Un fichier | `php artisan test --filter=NomDuTest` ou `php artisan test tests/Feature/...` |
| Formatage | `./vendor/bin/pint` (`--test` pour vérifier) |
| Files, Linux ou WSL | `php artisan horizon` |
| Files, Windows | `php artisan queue:work redis --queue=high,default` |
| Planification | `php artisan schedule:work` |
| Front, développement | `npm run dev` |
| Front, production | `npm run build` |
| Transcrire un lien en ligne de commande | `php artisan voxia:transcribe <lien> --user=<email>` |
| Mettre à jour yt-dlp | `php artisan voxia:ytdlp-update` |
| Évaluer F7 | `php artisan voxia:eval-scripts --fichier=tests/fixtures/analyse/pubs-test.json` (le défaut `tests/fixtures/pubs-reference.json` est absent du dépôt) |
| Donner le rôle administrateur à un compte existant | `php artisan voxia:make-admin <email>` |

---

## 9. Points fragiles qui gêneront la V2

Classés du plus au moins urgent pour la V2.

1. **Aucune Policy Laravel.** Le dossier `app/Policies/` n'existe pas. Le contrôle d'accès est une condition écrite à la main dans chaque contrôleur (`abort_unless($transcription->user_id === ...)`) ou dans chaque composant Livewire (`Auth::user()->transcriptions()->findOrFail(...)`). La règle V2 « policies sur chaque modèle » n'est donc pas satisfaite. Chaque nouveau modèle V2 (bibliothèque, projets, crédits, résumés) devra suivre le même schéma à la main, ce qui est fragile.

2. **Recherche plein texte non testée par défaut.** Les 11 tests de recherche sont ignorés sous SQLite. La commande `php artisan test` ne valide donc pas F5 (`ts_headline`, `to_tsvector`, configurations par langue). Il faut une passe avec PostgreSQL (`DB_CONNECTION=pgsql`) avant de toucher à la recherche.

3. **Commande `voxia:transcribe` cassée dès la boucle de suivi.** `app/Console/Commands/TranscrireVideo.php:12` importe `Illuminate\Support\Facades\Sleep`, qui n'existe pas dans Laravel 13.40 (vérifié dans `vendor/`). La classe correcte est `Illuminate\Support\Sleep`. Le test existant (`TranscrireVideoTest.php`) ne passe jamais dans cette boucle, donc il ne voit pas le problème. Ce point est déjà relevé dans `docs/mesures-etape-1.md` §4 et §5, non corrigé.

4. **Texte de transcription non neutralisé dans le prompt F7.** Les mots sont insérés tels quels entre `<transcription>` et `</transcription>`. Un mot contenant `</transcription>` fermerait la balise. L'impact est limité, parce que la sortie est contrainte par un schéma de nombres et que le texte affiché est reconstruit à partir des mots, puis échappé. La règle V2 exige une neutralisation explicite, et c'est un point à traiter à l'étape 1.

5. **Décompte des crédits IA non verrouillé à l'écriture.** `verifierCreditIa` verrouille le compte, mais `consommerCreditIa` (appelé dans `ServiceAnalyseScript`) n'est pas dans la même transaction verrouillée. Le compte des analyses en cours (`enCours`) limite le risque, sans l'éliminer strictement. À reprendre pour les crédits V2, qui seront plus nombreux.

6. **Tarifs du modèle codés dans la config.** `voxia.costs.script_analysis_*` (0,10 $ et 0,50 $ par million de jetons) sont des valeurs par défaut à vérifier sur la grille officielle avant toute facturation de crédits.

7. **Valeur d'offre inventée dans `.env.example`.** `VOXIA_PRO_AI_CREDITS_PER_MONTH=30` et le défaut de `config/voxia.php` : le cahier dit « encore à fixer ». Contraire à CLAUDE.md §7 (ne jamais inventer une valeur d'offre). Voir §10.

8. **Horizon ne tourne pas sous Windows.** Les tests de file (`queue:work`) et la chaîne complète ont été mesurés sans Horizon (`docs/mesures-etape-1.md`). Le comportement de production (supervision, délais) n'est pas vérifié sous Linux ou WSL.

9. **Anomalie non expliquée.** `Object of class App\Enums\SourceTranscription could not be converted to string` est apparue une fois dans le worker (mesures, §4 anomalie 3). Cause non trouvée. À surveiller.

10. **Mots limités à 255 caractères.** `transcription_words.texte` est un varchar(255). Un jeton plus long serait tronqué ou refusé selon le mode SQL. Peu probable, mais à tester.

11. **Stockage objet non vérifié.** `voxia.stockage.disque` vaut `local` par défaut. Le disque compatible S3 de production n'est configuré nulle part dans le dépôt. Les purges (`voxia:purger-medias`) supposent un disque qui supporte `delete`.

12. **Résidus de la charte « Signal ».** Le code garde des commentaires et des libellés « style Signal » (`AppServiceProvider.php:51`, `FortifyServiceProvider.php:37`, `lang/*/interface.php` : « Signal style guide ») alors que le cahier §7 remplace cette charte par « Nocturne ».

13. **Fichiers non commités dans l'arbre de travail.** `.env.example`, `config/voxia.php`, `phpunit.xml`, deux vues d'authentification et un test (`BoutonFacebookTest.php`) sont modifiés ou non suivis. La V2 ne doit pas être démarrée par-dessus sans les avoir traités ou mis de côté.

---

## 10. Questions à poser avant l'étape 1, et incohérences code / cahier

### 10.1 Questions

1. **Références manquantes.** `docs/v2/decisions.md` et `docs/voxia-cahier-des-charges-mvp.md` n'existent pas. Le cahier s'appelle `docs/cahier-des-charges.md`. Faut-il que j'utilise ce nom, et où sont les décisions V2 si elles existent ailleurs ?
2. **Définition de l'« étape 1 ».** La demande ne dit pas ce qu'elle contient. Est-ce le premier lot technique de la V2 (par exemple les policies, puis le modèle de langage générique), ou autre chose ?
3. **Charte.** Je garde « Nocturne » (cahier §7, CLAUDE.md §4) et non « Signal ». Confirmes-tu, et veux-tu que je nettoie les résidus « Signal » dans le code ?
4. **Prestataire de paiement.** Le cahier §8 laisse « Stripe ou Paddle, à décider ». Le code est en Paddle depuis le début. Paddle est-il définitif, et faut-il le noter dans le cahier ?
5. **Crédits IA du Pro.** Combien de crédits par mois ? `30` est aujourd'hui une valeur codée en dur, qui n'est pas dans l'offre. Je ne la modifie pas sans ta valeur (CLAUDE.md §7).
6. **Plan Business.** L'offre (`voxia-offre.md`) donne 29 $ et 150 h, mais le cahier dit « reporté en V2 ». Les valeurs de `config/voxia.php` (`business`) sont-elles validées ?
7. **Fournisseur de F7.** Le cahier §9 laisse « fournisseur du modèle de langage » ouvert. Le code est en Anthropic (`claude-haiku-5-5`). Est-ce validé, et les tarifs de la config sont-ils ceux à retenir ?
8. **Interface générique pour le modèle de langage.** `ScriptAnalyzer` ne sert qu'à F7 et renvoie des parties. Pour la V2 (résumé, réécriture, crédits), je propose une interface générique (`ModeleDeLangue`) dans `app/Contracts`, avec `ScriptAnalyzer` qui s'appuie dessus. Tu valides ?
9. **Policies.** Je propose de créer une Policy par modèle et de remplacer les contrôles manuels, en commençant par les modèles existants. Tu valides le périmètre ?
10. **Tests PostgreSQL.** Faut-il une étape de CI ou un script qui lance la suite avec PostgreSQL, pour que les 11 tests de recherche soient exécutés ?

### 10.2 Incohérences entre le code et le cahier

| Point | Cahier / offre / CLAUDE.md | Code | Proposition |
| --- | --- | --- | --- |
| Paiement | « Stripe ou Paddle, à décider » (§8) | Paddle (Cashier Paddle) | Trancher et mettre le cahier à jour |
| Crédits IA du Pro | « encore à fixer » (§5) | `30` codé en défaut | Retirer le défaut, ou le faire venir de la question 5 |
| Charte | Nocturne (§7) | Commentaires et libellés « Signal » | Nettoyer |
| Références de documents | `docs/cahier-des-charges.md` (nom réel) | La demande V2 cite `docs/voxia-cahier-des-charges-mvp.md` et `docs/v2/decisions.md`, absents | Confirmer les noms (question 1) |
| Commande `voxia:transcribe` | Chaîne testée (mesures) | Import faux, bug latent | Corriger avec un test (étape à planifier) |
| Quota et plan en gratuit | Une vidéo = 1 vidéo, durée libre (cahier §5) | Conforme | Aucune |
| Exports par plan | TXT, SRT en gratuit ; TXT, PDF, SRT, VTT au Pro (cahier §5) | Conforme | Aucune |
| Durée max F7 | 3 minutes (cahier F7) | 180 s (config) | Aucune |
| Recherche | Pro et Pack seulement (cahier F5) | Conforme (`recherche`) | Aucune |
| Business | Reporté en V2 (cahier §5) | Défini mais non vendable | Conforme |
| Langues de l'interface | Anglais et français (cahier §6) | Conforme | Aucune |
| Compte Google | Option à valider (cahier §5) | Désactivé par défaut, derrière un drapeau | Conforme |
| Limite de comptes gratuits par IP | Prévue (cahier §5) | `voxia.comptes.gratuits_par_ip = 2` | Conforme |
| Vidéos longues Gratuit | Aperçu de 10 minutes, relance après paiement (cahier §5) | Conforme (`est_apercu`, `relancerVersionComplete`) | Aucune |
