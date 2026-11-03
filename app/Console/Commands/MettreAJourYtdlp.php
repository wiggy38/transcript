<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Throwable;

/**
 * Met yt-dlp à jour. Planifiée chaque jour (routes/console.php). La sortie brute
 * va dans les journaux ; la console n'affiche que le résultat.
 */
class MettreAJourYtdlp extends Command
{
    protected $signature = 'voxia:ytdlp-update';

    protected $description = 'Met yt-dlp à jour';

    public function handle(): int
    {
        $binaire = (string) config('voxia.tools.ytdlp_binary');
        $delai = (int) config('voxia.tools.ytdlp_update_timeout_seconds');

        try {
            $miseAJour = Process::timeout($delai)->run([$binaire, '--update']);
        } catch (Throwable $erreur) {
            Log::error('yt-dlp: mise à jour impossible', ['erreur' => $erreur->getMessage()]);
            $this->error(__('videos.maj.echec'));

            return self::FAILURE;
        }

        $sortie = $miseAJour->output()."\n".$miseAJour->errorOutput();

        if ($miseAJour->failed()) {
            Log::warning('yt-dlp: mise à jour échouée', ['code' => $miseAJour->exitCode(), 'sortie' => $sortie]);
            $this->error(__('videos.maj.echec'));

            return self::FAILURE;
        }

        Log::info('yt-dlp: mise à jour', ['sortie' => $sortie]);

        $version = trim(Process::timeout($delai)->run([$binaire, '--version'])->output());

        $this->info(__('videos.maj.reussie', ['version' => $version !== '' ? $version : '?']));

        return self::SUCCESS;
    }
}
