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
            // Non fixé dans l'offre : « crédits IA élevés », à chiffrer en V2.
            'ai_credits_per_month' => (int) env('VOXIA_BUSINESS_AI_CREDITS_PER_MONTH', 0),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Fichiers importés
    |--------------------------------------------------------------------------
    */

    'uploads' => [
        'extensions' => ['mp3', 'm4a', 'wav', 'mp4', 'mov', 'webm'],
        'max_size_mb' => (int) env('VOXIA_UPLOAD_MAX_SIZE_MB', 2048),
    ],

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
    ],

    /*
    |--------------------------------------------------------------------------
    | Analyse de script publicitaire (F7)
    |--------------------------------------------------------------------------
    */

    'script_analysis' => [
        'max_video_seconds' => (int) env('VOXIA_SCRIPT_ANALYSIS_MAX_SECONDS', 180),
        'credits_per_analysis' => (int) env('VOXIA_SCRIPT_ANALYSIS_CREDITS', 1),
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
    | Coûts
    |--------------------------------------------------------------------------
    */

    'costs' => [
        'transcription_usd_per_hour' => (float) env('VOXIA_TRANSCRIPTION_COST_USD_PER_HOUR', 0.22),
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
