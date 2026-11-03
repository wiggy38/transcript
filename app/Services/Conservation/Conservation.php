<?php

namespace App\Services\Conservation;

use App\Jobs\SupprimerFichiersTranscriptions;
use App\Mail\RappelSuppression;
use App\Models\Transcription;
use App\Models\User;
use App\Services\Historique\SuppressionTranscription;
use App\Services\Plans\PlanFeatures;
use FilesystemIterator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Règles de conservation (cahier F5 et §5), exécutées par les commandes planifiées :
 *
 *   - historique : les transcriptions d'un plan à durée limitée (Gratuit, 30 jours) sont supprimées ;
 *   - rappel : un email part « jours avant » la suppression, une seule fois par transcription ;
 *   - médias : MP3 et fichiers importés plus vieux que « retention.media_days » partent, pour tous les plans.
 *
 * Un fichier ou une transcription conservés ne sont jamais touchés avant leur échéance.
 */
class Conservation
{
    public function __construct(
        private readonly PlanFeatures $plans,
        private readonly SuppressionTranscription $suppression,
    ) {}

    /** Supprime les transcriptions expirées. Renvoie leur nombre. */
    public function purgerHistorique(): int
    {
        $total = 0;

        User::query()->chunkById(200, function ($utilisateurs) use (&$total) {
            foreach ($utilisateurs as $utilisateur) {
                $jours = $this->plans->joursConservation($utilisateur);

                if ($jours === null) {
                    continue;
                }

                $identifiants = $utilisateur->transcriptions()
                    ->where('created_at', '<', now()->subDays($jours))
                    ->pluck('id')
                    ->all();

                $total += $this->suppression->supprimer($identifiants);
            }
        });

        return $total;
    }

    /** Envoie le rappel avant suppression, par compte. Renvoie le nombre de transcriptions signalées. */
    public function rappelerSuppressions(): int
    {
        $total = 0;

        User::query()->chunkById(200, function ($utilisateurs) use (&$total) {
            foreach ($utilisateurs as $utilisateur) {
                $jours = $this->plans->joursConservation($utilisateur);
                $avantRappel = $this->plans->joursAvantRappel($utilisateur);

                if ($jours === null || $avantRappel <= 0) {
                    continue;
                }

                // Transcriptions entrées dans la fenêtre « rappel » mais pas encore expirées, sans rappel envoyé.
                $aSignaler = $utilisateur->transcriptions()
                    ->where('created_at', '<=', now()->subDays($jours - $avantRappel))
                    ->where('created_at', '>', now()->subDays($jours))
                    ->whereNull('date_rappel_suppression')
                    ->orderBy('created_at')
                    ->get();

                if ($aSignaler->isEmpty()) {
                    continue;
                }

                Mail::to($utilisateur)->send(new RappelSuppression($utilisateur, $aSignaler, $jours, $avantRappel));

                Transcription::query()
                    ->whereIn('id', $aSignaler->pluck('id'))
                    ->update(['date_rappel_suppression' => now()]);

                $total += $aSignaler->count();
            }
        });

        return $total;
    }

    /**
     * Retire les MP3 plus vieux que la durée de conservation des médias, puis les fichiers
     * importés de même âge (y compris les envois de morceaux restés incomplets).
     *
     * @return array{mp3: int, fichiers: int}
     */
    public function purgerMedias(): array
    {
        $limite = now()->subDays((int) config('voxia.retention.media_days'));
        $mp3 = 0;

        Transcription::query()
            ->whereNotNull('mp3_chemin')
            ->where('created_at', '<', $limite)
            ->chunkById(200, function ($lot) use (&$mp3) {
                $identifiants = $lot->pluck('id')->all();

                Transcription::query()->whereIn('id', $identifiants)->update(['mp3_chemin' => null]);
                SupprimerFichiersTranscriptions::dispatch($identifiants);

                $mp3 += count($identifiants);
            });

        return ['mp3' => $mp3, 'fichiers' => $this->purgerFichiersImportes($limite->getTimestamp())];
    }

    /** Fichiers importés et morceaux plus vieux que la limite, dossiers vides compris. */
    private function purgerFichiersImportes(int $horodatageLimite): int
    {
        $dossier = rtrim((string) config('voxia.uploads.dossier'), '/\\');

        if (! is_dir($dossier)) {
            return 0;
        }

        $supprimes = 0;
        $parcours = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dossier, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($parcours as $element) {
            if ($element->isFile() && $element->getMTime() < $horodatageLimite) {
                if (@unlink($element->getPathname())) {
                    $supprimes++;
                }
            } elseif ($element->isDir() && File::isEmptyDirectory($element->getPathname())) {
                @rmdir($element->getPathname());
            }
        }

        return $supprimes;
    }
}
