<?php

namespace App\Jobs;

use App\Mail\ExportDonneesPret;
use App\Models\Transcription;
use App\Models\User;
use App\Support\Horodatage;
use App\Support\Langue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\File;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

/**
 * Export de toutes les données d'un compte (réglages, « Vos données ») : un ZIP contenant le compte,
 * chaque transcription (JSON, texte horodaté, MP3 si présent, analyse de script si présente) et la
 * consommation de quotas. Préparé par la file pour qu'aucune requête web n'attende ; le lien signé
 * arrive ensuite par email.
 */
class ExporterDonneesCompte implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $utilisateurId) {}

    /** Dossier de l'export d'un compte sur le disque de stockage. */
    public static function dossier(int $utilisateurId): string
    {
        return 'exports/'.$utilisateurId;
    }

    /** Chemin du ZIP sur le disque de stockage. */
    public static function chemin(int $utilisateurId): string
    {
        return self::dossier($utilisateurId).'/voxia-donnees.zip';
    }

    public function handle(): void
    {
        $utilisateur = User::query()->find($this->utilisateurId);

        if ($utilisateur === null) {
            return;
        }

        $disque = Storage::disk((string) config('voxia.stockage.disque'));
        $archiveTemporaire = tempnam(sys_get_temp_dir(), 'voxia-export-');

        if ($archiveTemporaire === false) {
            throw new RuntimeException('Impossible de créer le fichier temporaire de l\'export.');
        }

        $archive = new ZipArchive;

        if ($archive->open($archiveTemporaire, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Impossible d\'ouvrir l\'archive de l\'export.');
        }

        // Copies locales des MP3 (disque distant) : gardées jusqu'à la fermeture de l'archive.
        $copiesTemporaires = [];

        $archive->addFromString('compte.json', $this->json([
            'nom' => $utilisateur->name,
            'email' => $utilisateur->email,
            'langue' => $utilisateur->locale,
            'plan' => $utilisateur->plan?->value,
            'inscription' => $utilisateur->created_at?->toIso8601String(),
            'email_verifie' => $utilisateur->email_verified_at?->toIso8601String(),
        ]));

        $utilisateur->transcriptions()
            ->with(['paragraphs', 'scriptAnalysis.parts'])
            ->orderBy('id')
            ->chunkById(50, function ($transcriptions) use ($archive, $disque, &$copiesTemporaires) {
                foreach ($transcriptions as $transcription) {
                    $this->ajouterTranscription($archive, $disque, $transcription, $copiesTemporaires);
                }
            });

        $archive->addFromString('consommation.json', $this->json(
            $utilisateur->usageRecords()->orderBy('id')->get()->map(fn ($ligne) => [
                'date' => $ligne->created_at?->toIso8601String(),
                'unite' => $ligne->unite,
                'quantite' => $ligne->quantite,
                'plan' => $ligne->plan,
                'motif' => $ligne->motif,
            ])->all(),
        ));

        $archive->close();

        foreach ($copiesTemporaires as $copie) {
            @unlink($copie);
        }

        $disque->putFileAs(self::dossier($utilisateur->getKey()), new File($archiveTemporaire), 'voxia-donnees.zip');
        @unlink($archiveTemporaire);

        $lien = URL::temporarySignedRoute(
            'reglages.donnees.telecharger',
            now()->addMinutes((int) config('voxia.donnees.duree_lien_minutes')),
            ['utilisateur' => $utilisateur->getKey()],
        );

        Mail::to($utilisateur->email)->queue(new ExportDonneesPret($utilisateur->name, $lien, Langue::pour($utilisateur)));
    }

    /** @param  list<string>  $copiesTemporaires  Copies locales des MP3 à supprimer après l'archive. */
    private function ajouterTranscription(ZipArchive $archive, FilesystemAdapter $disque, Transcription $transcription, array &$copiesTemporaires): void
    {
        $dossier = 'transcriptions/'.$transcription->getKey().'-'.(Str::slug((string) $transcription->titre) ?: 'sans-titre');

        $archive->addFromString($dossier.'/transcription.json', $this->json([
            'titre' => $transcription->titre,
            'source' => $transcription->source?->value,
            'url_origine' => $transcription->url_origine,
            'id_video' => $transcription->id_video,
            'chaine' => $transcription->chaine,
            'duree_secondes' => $transcription->duree_secondes,
            'langue_detectee' => $transcription->langue_detectee,
            'langue_forcee' => $transcription->langue_forcee,
            'statut' => $transcription->statut?->value,
            'nombre_mots' => $transcription->nombre_mots,
            'creee' => $transcription->created_at?->toIso8601String(),
            'terminee' => $transcription->date_termine?->toIso8601String(),
            'paragraphes' => $transcription->paragraphs->map(fn ($p) => [
                'debut' => Horodatage::format((int) $p->debut_ms),
                'fin' => Horodatage::format((int) $p->fin_ms),
                'texte' => $p->texte,
            ])->all(),
        ]));

        $texte = $transcription->paragraphs
            ->map(fn ($p) => '['.Horodatage::format((int) $p->debut_ms).'] '.$p->texte)
            ->implode("\n\n");
        $archive->addFromString($dossier.'/transcription.txt', $texte === '' ? '' : $texte."\n");

        $this->ajouterMp3($archive, $disque, $transcription, $dossier, $copiesTemporaires);

        $analyse = $transcription->scriptAnalysis;

        if ($analyse !== null) {
            $archive->addFromString($dossier.'/analyse.json', $this->json([
                'statut' => $analyse->statut?->value,
                'fournisseur' => $analyse->fournisseur,
                'parties' => $analyse->parts->map(fn ($partie) => [
                    'type' => $partie->type?->value,
                    'debut' => Horodatage::format((int) $partie->debut_ms),
                    'fin' => Horodatage::format((int) $partie->fin_ms),
                    'texte' => $partie->texte,
                ])->all(),
            ]));
        }
    }

    /** MP3 de la transcription, s'il existe encore (la conservation peut l'avoir retiré). */
    private function ajouterMp3(ZipArchive $archive, FilesystemAdapter $disque, Transcription $transcription, string $dossier, array &$copiesTemporaires): void
    {
        if ($transcription->mp3_chemin === null || ! $disque->exists($transcription->mp3_chemin)) {
            return;
        }

        // Disque local : le fichier est ajouté à l'archive sans copie.
        if (config('filesystems.disks.'.config('voxia.stockage.disque').'.driver') === 'local') {
            $archive->addFile($disque->path($transcription->mp3_chemin), $dossier.'/audio.mp3');

            return;
        }

        // Disque distant : copie locale, gardée jusqu'à la fermeture de l'archive.
        $copie = tempnam(sys_get_temp_dir(), 'voxia-mp3-');
        $source = $disque->readStream($transcription->mp3_chemin);
        $cible = fopen($copie, 'wb');
        stream_copy_to_stream($source, $cible);
        fclose($cible);
        fclose($source);

        $copiesTemporaires[] = $copie;
        $archive->addFile($copie, $dossier.'/audio.mp3');
    }

    private function json(mixed $donnees): string
    {
        return json_encode($donnees, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
