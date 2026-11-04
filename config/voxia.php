<?php

/*
|--------------------------------------------------------------------------
| Voxia — valeurs métier
|--------------------------------------------------------------------------
|
| Toutes les règles métier du MVP (plans, quotas, formats, rétention,
| sous-titres, coûts, seuils d'alerte) sont centralisées ici. Chaque valeur
| se règle depuis le .env ; les valeurs par défaut correspondent au cahier
| des charges et à l'offre validée le 3 novembre 2026.
|
| Les montants sont en dollars US. Les durées sont en secondes, sauf mention
| contraire. Les casts (int) et (float) sont volontaires : env() renvoie
| toujours une chaîne.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Langues de l'interface
    |--------------------------------------------------------------------------
    */

    'locales' => ['en', 'fr'],

    /*
    |--------------------------------------------------------------------------
    | Plans
    |--------------------------------------------------------------------------
    |
    | « sellable » : le plan peut être vendu dans le MVP. Le plan Business est
    | défini ici mais reste non vendable tant que la V2 n'est pas livrée.
    |
    */

    'plans' => [

        'free' => [
            'label' => 'Gratuit',
            'sellable' => true,
            'price_usd' => 0,
            'videos_per_month' => (int) env('VOXIA_FREE_VIDEOS_PER_MONTH', 5),
            'max_video_seconds' => (int) env('VOXIA_FREE_MAX_VIDEO_SECONDS', 600),
            'exports' => ['txt', 'srt'],
            'history_days' => (int) env('VOXIA_FREE_HISTORY_DAYS', 30),
            'history_reminder_days_before' => (int) env('VOXIA_FREE_HISTORY_REMINDER_DAYS_BEFORE', 3),
            // Recherche plein texte dans l'historique : réservée aux plans payants (offre).
            'recherche' => false,
            'ai_credits_per_month' => 0,
        ],

        'pro' => [
            'label' => 'Pro',
            'sellable' => true,
            'price_monthly_usd' => (float) env('VOXIA_PRO_PRICE_MONTHLY_USD', 12),
            'price_yearly_usd' => (float) env('VOXIA_PRO_PRICE_YEARLY_USD', 96),
            'hours_per_month' => (int) env('VOXIA_PRO_HOURS_PER_MONTH', 30),
            'max_video_seconds' => (int) env('VOXIA_PRO_MAX_VIDEO_SECONDS', 3 * 3600),
            'exports' => ['txt', 'pdf', 'srt', 'vtt'],
            // Historique illimité (null) et recherche incluse (offre).
            'history_days' => null,
            'recherche' => true,
            // Provisoire : à fixer avant la mise en vente.
            'ai_credits_per_month' => (int) env('VOXIA_PRO_AI_CREDITS_PER_MONTH', 30),
        ],

        'pack' => [
            'label' => 'Pack',
            'sellable' => true,
            'price_usd' => (float) env('VOXIA_PACK_PRICE_USD', 5),
            'hours' => (int) env('VOXIA_PACK_HOURS', 10),
            'validity_months' => (int) env('VOXIA_PACK_VALIDITY_MONTHS', 6),
            'max_video_seconds' => (int) env('VOXIA_PACK_MAX_VIDEO_SECONDS', 3 * 3600),
            'exports' => ['txt', 'pdf', 'srt', 'vtt'],
            'history_days' => null,
            'recherche' => true,
            'ai_credits_per_month' => 0,
        ],

        'business' => [
            'label' => 'Business',
            'sellable' => false,
            'price_monthly_usd' => (float) env('VOXIA_BUSINESS_PRICE_MONTHLY_USD', 29),
            'price_yearly_usd' => (float) env('VOXIA_BUSINESS_PRICE_YEARLY_USD', 276),
            'hours_per_month' => (int) env('VOXIA_BUSINESS_HOURS_PER_MONTH', 150),
            // Valeur de l'offre (5 h) ; le cahier ne la fixe pas, à confirmer.
            'max_video_seconds' => (int) env('VOXIA_BUSINESS_MAX_VIDEO_SECONDS', 5 * 3600),
            'exports' => ['txt', 'pdf', 'srt', 'vtt'],
            'history_days' => null,
            'recherche' => true,
            // Non fixé dans l'offre : « crédits IA élevés », à chiffrer en V2.
            'ai_credits_per_month' => (int) env('VOXIA_BUSINESS_AI_CREDITS_PER_MONTH', 0),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Droits par plan (service App\Services\Plans\PlanFeatures)
    |--------------------------------------------------------------------------
    |
    | Tant que la facturation (étape 4) n'est pas branchée, tout le monde est
    | au plan Gratuit. « plan_reel » à true sert aux tests et à la démo : le plan
    | enregistré sur le compte (users.plan) est alors pris en compte.
    |
    */

    'droits' => [
        'plan_reel' => filter_var(env('VOXIA_PLAN_REEL', false), FILTER_VALIDATE_BOOLEAN),
    ],

    /*
    |--------------------------------------------------------------------------
    | Historique
    |--------------------------------------------------------------------------
    */

    'historique' => [
        'par_page' => (int) env('VOXIA_HISTORY_PER_PAGE', 20),
    ],

    /*
    |--------------------------------------------------------------------------
    | Recherche plein texte (PostgreSQL)
    |--------------------------------------------------------------------------
    |
    | Chaque langue de transcription a une configuration de recherche PostgreSQL
    | (stemming, mots vides). Un index GIN partiel existe par configuration
    | (migration create_transcription_paragraphs / ajouter_recherche). Une langue
    | absente de la liste utilise « simple » : pas de racinisation, mais la
    | recherche reste exacte.
    |
    | « extrait_mots » : nombre de mots affichés autour du terme trouvé.
    |
    */

    'recherche' => [
        'configurations' => [
            'fr' => 'french',
            'en' => 'english',
            'es' => 'spanish',
            'de' => 'german',
            'it' => 'italian',
            'pt' => 'portuguese',
        ],
        'configuration_defaut' => 'simple',
        'extrait_mots' => (int) env('VOXIA_SEARCH_SNIPPET_WORDS', 24),
        'longueur_max_terme' => 200,
    ],

    /*
    |--------------------------------------------------------------------------
    | Conservation de l'historique et des fichiers
    |--------------------------------------------------------------------------
    |
    | Les transcriptions du plan Gratuit sont supprimées après « history_days »
    | (config « plans.free »), avec un email « rappel » « history_reminder_days_before »
    | jours avant. Les MP3 et les fichiers importés suivent « retention.media_days »,
    | pour tous les plans. « heure » : heure locale des commandes planifiées.
    |
    */

    'conservation' => [
        'heure_purge' => env('VOXIA_CONSERVATION_AT', '03:30'),
        'heure_rappel' => env('VOXIA_CONSERVATION_REMINDER_AT', '09:00'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Fichiers importés
    |--------------------------------------------------------------------------
    */

    'uploads' => [
        'extensions' => ['mp3', 'm4a', 'wav', 'mp4', 'mov', 'webm'],
        'max_size_mb' => (int) env('VOXIA_UPLOAD_MAX_SIZE_MB', 2048),
        // Envoi découpé : taille d'un morceau, sous la limite par défaut de PHP (2 Mo).
        'morceau_octets' => (int) env('VOXIA_UPLOAD_CHUNK_BYTES', 1024 * 1024),
        // Dossier des morceaux et des fichiers reçus, un sous-dossier par utilisateur.
        'dossier' => env('VOXIA_UPLOAD_DIRECTORY', storage_path('app/televersements')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Aperçu d'un lien (titre, durée, miniature)
    |--------------------------------------------------------------------------
    |
    | Les métadonnées sont lues par la file ; l'écran interroge le résultat.
    | « cache_secondes » : durée de conservation de l'aperçu.
    |
    */

    'apercu' => [
        'cache_secondes' => (int) env('VOXIA_APERCU_CACHE_SECONDS', 900),
    ],

    /*
    |--------------------------------------------------------------------------
    | Langues forçables
    |--------------------------------------------------------------------------
    |
    | Liste proposée à l'utilisateur ; la détection automatique reste le défaut.
    | Validées avec ElevenLabs Scribe v2 (codes ISO 639-1 : fr, en, es, de, it, pt).
    |
    */

    'langues_forcables' => ['fr', 'en', 'es', 'de', 'it', 'pt'],

    /*
    |--------------------------------------------------------------------------
    | Conservation des fichiers
    |--------------------------------------------------------------------------
    |
    | Durée de conservation des MP3 et des fichiers importés.
    |
    */

    'retention' => [
        'media_days' => (int) env('VOXIA_MEDIA_RETENTION_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Audio
    |--------------------------------------------------------------------------
    */

    'audio' => [
        'mp3_bitrate_kbps' => (int) env('VOXIA_MP3_BITRATE_KBPS', 192),
        // Longueur maximale du nom de fichier, en octets (les systèmes limitent à 255).
        'filename_max_bytes' => (int) env('VOXIA_AUDIO_FILENAME_MAX_BYTES', 200),
        'probe_timeout_seconds' => (int) env('VOXIA_AUDIO_PROBE_TIMEOUT_SECONDS', 30),
        'extraction_timeout_seconds' => (int) env('VOXIA_AUDIO_EXTRACTION_TIMEOUT_SECONDS', 900),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sous-titres (SRT et VTT)
    |--------------------------------------------------------------------------
    */

    'subtitles' => [
        'max_chars_per_line' => (int) env('VOXIA_SUBTITLE_MAX_CHARS_PER_LINE', 42),
        'max_lines' => (int) env('VOXIA_SUBTITLE_MAX_LINES', 2),
        'min_duration_seconds' => (int) env('VOXIA_SUBTITLE_MIN_DURATION_SECONDS', 1),
        'max_duration_seconds' => (int) env('VOXIA_SUBTITLE_MAX_DURATION_SECONDS', 7),
        // Coupure à une virgule ou un point-virgule seulement si le sous-titre occupe au moins cette part de la place.
        'coupure_ponctuation_faible_ratio' => (float) env('VOXIA_SUBTITLE_WEAK_BREAK_RATIO', 0.75),
    ],

    /*
    |--------------------------------------------------------------------------
    | Exports (F4)
    |--------------------------------------------------------------------------
    |
    | Polices dans resources/fonts : Geist pour le texte, Noto Sans en secours pour les
    | caractères non latins. Marge en millimètres.
    |
    */

    'exports' => [
        'pdf' => [
            'marge_mm' => (int) env('VOXIA_PDF_MARGIN_MM', 22),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Analyse de script publicitaire (F7)
    |--------------------------------------------------------------------------
    */

    'script_analysis' => [
        'max_video_seconds' => (int) env('VOXIA_SCRIPT_ANALYSIS_MAX_SECONDS', 180),
        'credits_per_analysis' => (int) env('VOXIA_SCRIPT_ANALYSIS_CREDITS', 1),
        // Relances après une réponse invalide ou un échec d'appel (le total doit rester sous 15 s).
        'relances' => (int) env('VOXIA_SCRIPT_ANALYSIS_RETRIES', 1),
        // Délai d'un appel au fournisseur, et de la tâche entière.
        'timeout_seconds' => (int) env('VOXIA_SCRIPT_ANALYSIS_TIMEOUT_SECONDS', 30),
        'connect_timeout_seconds' => (int) env('VOXIA_SCRIPT_ANALYSIS_CONNECT_TIMEOUT_SECONDS', 10),
        'job_timeout_seconds' => (int) env('VOXIA_SCRIPT_ANALYSIS_JOB_TIMEOUT_SECONDS', 90),
        'max_output_tokens' => (int) env('VOXIA_SCRIPT_ANALYSIS_MAX_OUTPUT_TOKENS', 1024),
        // Écart maximal, en millisecondes, entre une borne renvoyée et le mot le plus proche.
        'recalage_max_ms' => (int) env('VOXIA_SCRIPT_ANALYSIS_SNAP_MAX_MS', 2000),
        // Largeur affichée d'une partie non détectée dans la barre (échelle relative, sans durée réelle).
        'poids_partie_absente_ms' => (int) env('VOXIA_SCRIPT_ANALYSIS_ABSENT_WEIGHT_MS', 1500),
        // Évaluation (commande voxia:eval-scripts) : part de limites justes visée, tolérance, délai max.
        'eval' => [
            'cible' => (float) env('VOXIA_SCRIPT_EVAL_TARGET', 0.8),
            'tolerance_ms' => (int) env('VOXIA_SCRIPT_EVAL_TOLERANCE_MS', 2000),
            'delai_max_secondes' => (int) env('VOXIA_SCRIPT_EVAL_MAX_SECONDS', 15),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | Un email de fin de traitement est envoyé au-delà de cette durée de vidéo.
    |
    */

    'notifications' => [
        'long_video_email_after_seconds' => (int) env('VOXIA_LONG_VIDEO_EMAIL_AFTER_SECONDS', 900),
    ],

    /*
    |--------------------------------------------------------------------------
    | Comptes
    |--------------------------------------------------------------------------
    |
    | « gratuits_par_ip » : nombre maximal de comptes gratuits créés depuis une
    | même adresse IP (2 par défaut, validé le 3 novembre 2026). null = aucune limite.
    | « google_actif » : connexion Google, désactivée par
    | défaut. Les limites de tentatives servent à freiner les attaques, pas l'offre.
    |
    */

    'comptes' => [
        'google_actif' => filter_var(env('VOXIA_GOOGLE_LOGIN', false), FILTER_VALIDATE_BOOLEAN),
        'gratuits_par_ip' => ($valeur = env('VOXIA_FREE_ACCOUNTS_PER_IP', 2)) === null || $valeur === ''
            ? null
            : (int) $valeur,
        'limites' => [
            'inscriptions_par_heure_ip' => (int) env('VOXIA_REGISTER_PER_HOUR_PER_IP', 10),
            'mots_de_passe_oublies_par_heure' => (int) env('VOXIA_PASSWORD_RESET_PER_HOUR', 5),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Coûts
    |--------------------------------------------------------------------------
    */

    'costs' => [
        'transcription_usd_per_hour' => (float) env('VOXIA_TRANSCRIPTION_COST_USD_PER_HOUR', 0.22),
        // Analyse de script (Claude Haiku 5.5, par million de jetons, tarif officiel).
        'script_analysis_input_usd_per_mtok' => (float) env('VOXIA_SCRIPT_ANALYSIS_INPUT_USD_PER_MTOK', 0.10),
        'script_analysis_output_usd_per_mtok' => (float) env('VOXIA_SCRIPT_ANALYSIS_OUTPUT_USD_PER_MTOK', 0.50),
    ],

    /*
    |--------------------------------------------------------------------------
    | Transcription (moteur derrière App\Contracts\TranscriptionEngine)
    |--------------------------------------------------------------------------
    |
    | Limites lues dans la documentation ElevenLabs (Speech-to-Text) : 3 Go et
    | 10 heures par fichier en mode standard. Les tentatives ne servent qu'aux
    | échecs d'appel ; une limite locale dépassée échoue sans appel.
    |
    */

    'transcription' => [
        'attempts' => (int) env('VOXIA_TRANSCRIPTION_ATTEMPTS', 3),
        'retry_delay_ms' => (int) env('VOXIA_TRANSCRIPTION_RETRY_DELAY_MS', 2000),
        'timeout_seconds' => (int) env('VOXIA_TRANSCRIPTION_TIMEOUT_SECONDS', 900),
        'connect_timeout_seconds' => (int) env('VOXIA_TRANSCRIPTION_CONNECT_TIMEOUT_SECONDS', 10),
        'max_size_mb' => (int) env('VOXIA_TRANSCRIPTION_MAX_SIZE_MB', 3072),
        'max_duration_seconds' => (int) env('VOXIA_TRANSCRIPTION_MAX_DURATION_SECONDS', 10 * 3600),
        'error_body_log_chars' => 500,
    ],

    /*
    |--------------------------------------------------------------------------
    | Paragraphes (regroupement des mots transcrits)
    |--------------------------------------------------------------------------
    |
    | Valeurs proposées, à valider (avancement, point P12). Une pause égale ou
    | supérieure à « pause_ms » coupe toujours le paragraphe. Une fin de phrase
    | ne coupe qu'à partir de « fin_de_phrase_min_mots ». « max_mots » coupe
    | un discours sans pause.
    |
    */

    'paragraphes' => [
        'pause_ms' => (int) env('VOXIA_PARAGRAPHE_PAUSE_MS', 1200),
        'fin_de_phrase_min_mots' => (int) env('VOXIA_PARAGRAPHE_FIN_DE_PHRASE_MIN_MOTS', 20),
        'max_mots' => (int) env('VOXIA_PARAGRAPHE_MAX_MOTS', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Surveillance
    |--------------------------------------------------------------------------
    |
    | Alerte lorsque la part des téléchargements en échec dépasse ce seuil
    | (0.05 = 5 %).
    |
    */

    'monitoring' => [
        'download_failure_alert_threshold' => (float) env('VOXIA_DOWNLOAD_FAILURE_ALERT_THRESHOLD', 0.05),
    ],

    /*
    |--------------------------------------------------------------------------
    | Traitement en file d'attente (une tâche par vidéo)
    |--------------------------------------------------------------------------
    |
    | « delai_total_secondes » est le garde-fou de la tâche entière. Chaque étape
    | a déjà son délai (outils, moteur) : ce total doit rester supérieur à leur
    | somme pour qu'aucune tâche valide ne soit coupée. « tentatives » ne couvre
    | que les étapes sans nouvelle tentative interne (métadonnées, stockage) ; le
    | téléchargement et la transcription réessaient dans leur propre service.
    |
    */

    'traitement' => [
        'delai_total_secondes' => (int) env('VOXIA_JOB_TIMEOUT_SECONDS', 7200),
        'tentatives' => [
            'metadonnees' => (int) env('VOXIA_METADATA_ATTEMPTS', 2),
            'stockage' => (int) env('VOXIA_STORAGE_ATTEMPTS', 3),
        ],
        'delai_entre_tentatives_ms' => (int) env('VOXIA_JOB_RETRY_DELAY_MS', 2000),
        // Files Horizon : « prioritaire » est servie avant « standard » (voir config/horizon.php).
        'files' => [
            'prioritaire' => 'high',
            'standard' => 'default',
        ],
        // Plans servis dans la file prioritaire.
        'plans_prioritaires' => ['pro'],
        // Suivi de la progression dans la commande voxia:transcribe.
        'suivi_intervalle_secondes' => (int) env('VOXIA_CLI_POLL_SECONDS', 2),
        'suivi_delai_secondes' => (int) env('VOXIA_CLI_TIMEOUT_SECONDS', 7200),
    ],

    /*
    |--------------------------------------------------------------------------
    | Stockage des MP3
    |--------------------------------------------------------------------------
    |
    | Disque Laravel (config/filesystems.php) : « local » en développement,
    | un disque compatible S3 en production.
    |
    */

    'stockage' => [
        'disque' => env('VOXIA_STORAGE_DISK', 'local'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Liens de téléchargement
    |--------------------------------------------------------------------------
    |
    | Durée de validité, en minutes, des liens signés qui servent le MP3 et les exports.
    | Un lien expiré répond 403 ; revenir sur la transcription en donne un nouveau.
    |
    */

    'telechargement' => [
        'duree_lien_minutes' => (int) env('VOXIA_LIEN_DUREE_MINUTES', 15),
    ],

    /*
    |--------------------------------------------------------------------------
    | Outils externes
    |--------------------------------------------------------------------------
    |
    | Chemins des binaires et proxy utilisé par yt-dlp. Les clés d'API vivent
    | dans config/services.php.
    |
    */

    'tools' => [
        'ytdlp_binary' => env('YTDLP_BINARY', 'yt-dlp'),
        'ffmpeg_binary' => env('FFMPEG_BINARY', 'ffmpeg'),
        'ffprobe_binary' => env('FFPROBE_BINARY', 'ffprobe'),
        'proxy_url' => env('VOXIA_PROXY_URL'),
        'download' => [
            // Délai maximal par appel yt-dlp (secondes).
            'timeout_seconds' => (int) env('VOXIA_DOWNLOAD_TIMEOUT_SECONDS', 900),
            'metadata_timeout_seconds' => (int) env('VOXIA_METADATA_TIMEOUT_SECONDS', 60),
            // Tentatives par téléchargement, uniquement sur erreur technique.
            'attempts' => (int) env('VOXIA_DOWNLOAD_ATTEMPTS', 3),
            // Dossier parent des dossiers temporaires, un par tâche.
            'temp_directory' => env('VOXIA_TEMP_DIRECTORY', storage_path('app/temp')),
        ],
        // Mise à jour quotidienne de yt-dlp (heure locale, format HH:MM).
        'ytdlp_update_at' => env('VOXIA_YTDLP_UPDATE_AT', '04:00'),
        'ytdlp_update_timeout_seconds' => (int) env('VOXIA_YTDLP_UPDATE_TIMEOUT_SECONDS', 300),
    ],

];
