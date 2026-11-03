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
    ],

];
