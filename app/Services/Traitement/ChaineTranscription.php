<?php

namespace App\Services\Traitement;

use App\Contracts\AudioExtractor;
use App\Contracts\TranscriptionEngine;
use App\Contracts\VideoFetcher;
use App\Data\AudioTelecharge;
use App\Data\ExtraitAudio;
use App\Data\LienVideo;
use App\Data\MotTranscrit;
use App\Data\Paragraphe;
use App\Data\TranscriptionBrute;
use App\Enums\EtapeTraitement;
use App\Enums\MotifEchec;
use App\Enums\Plan;
use App\Enums\StatutTranscription;
use App\Events\TranscriptionCompleted;
use App\Events\TranscriptionFailed;
use App\Exceptions\ErreurVideo;
use App\Models\ProcessingLog;
use App\Models\Transcription;
use App\Models\TranscriptionParagraph;
use App\Models\TranscriptionWord;
use App\Services\Transcription\ParagraphBuilder;
use Closure;
use Illuminate\Http\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File as Fichiers;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Chaîne complète d'une transcription, exécutée par la tâche ProcessTranscription :
 *
 *   métadonnées → téléchargement → MP3 → transcription → paragraphes → enregistrement
 *
 * Chaque étape écrit une ligne dans processing_logs (durée, succès, message, coût).
 * Le statut suit la phase (Téléchargement, Transcription, Terminé, Échec) ; les dates
 * suivent chaque étape. Tout échec passe la transcription en Échec avec son motif.
 * Les fichiers temporaires sont supprimés dans tous les cas, succès comme échec.
 *
 * Aucune consommation n'est enregistrée ici : elle sera déclenchée par
 * TranscriptionCompleted, et uniquement à l'état Terminé.
 */
final class ChaineTranscription
{
    public function __construct(
        private readonly VideoFetcher $videos,
        private readonly AudioExtractor $extracteur,
        private readonly TranscriptionEngine $moteur,
        private readonly ParagraphBuilder $paragraphes,
    ) {}

    /**
     * @param  string|null  $cheminFichier  Fichier local à traiter (source « fichier »). Nul pour un lien.
     */
    public function executer(Transcription $transcription, ?string $cheminFichier = null): void
    {
        $transcription->loadMissing('user');

        $audio = null;
        $dossierMp3 = $this->dossierTemporaire();
        $reussie = false;

        try {
            $this->changerStatut($transcription, StatutTranscription::Telechargement);

            $lien = $this->etapeMetadonnees($transcription, $cheminFichier);

            $source = $this->etapeTelechargement($transcription, $cheminFichier, $lien, $audio);

            $extrait = $this->etapeMp3($transcription, $source, $dossierMp3, $cheminFichier !== null);

            $this->changerStatut($transcription, StatutTranscription::Transcription);
            $brute = $this->etapeTranscription($transcription, $extrait);

            $paragraphes = $this->etapeParagraphes($transcription, $brute);

            $this->etapeEnregistrement($transcription, $extrait, $brute, $paragraphes);

            $reussie = true;
        } catch (ErreurVideo $erreur) {
            $this->echouer($transcription, $erreur->motif);
        } catch (Throwable $erreur) {
            report($erreur);
            $this->echouer($transcription, MotifEchec::ErreurTechnique);
        } finally {
            $audio?->supprimer();
            Fichiers::deleteDirectory($dossierMp3);
        }

        // Hors du try : une erreur d'écouteur ne doit pas faire repasser une transcription terminée en échec.
        if ($reussie) {
            event(new TranscriptionCompleted($transcription));
        }
    }

    /**
     * Passe en échec une transcription restée en cours (tâche interrompue, par exemple
     * par le délai total). Sans effet si elle est déjà terminée ou en échec.
     */
    public function interrompre(Transcription $transcription, MotifEchec $motif): void
    {
        if (in_array($transcription->statut, [StatutTranscription::Termine, StatutTranscription::Echec], true)) {
            return;
        }

        $this->echouer($transcription, $motif);
    }

    public function echouer(Transcription $transcription, MotifEchec $motif): void
    {
        $transcription->update([
            'statut' => StatutTranscription::Echec,
            'motif_echec' => $motif,
            'date_echec' => now(),
        ]);

        event(new TranscriptionFailed($transcription, $motif));
    }

    /** Métadonnées (lien) ou nom du fichier local. Renvoie le lien, nul pour un fichier. */
    private function etapeMetadonnees(Transcription $transcription, ?string $cheminFichier): ?LienVideo
    {
        return $this->mesurer($transcription, EtapeTraitement::Metadonnees, function (BilanEtape $bilan) use ($transcription, $cheminFichier) {
            if ($cheminFichier !== null) {
                $transcription->update([
                    'titre' => pathinfo($cheminFichier, PATHINFO_FILENAME),
                    'date_metadonnees' => now(),
                ]);
                $bilan->message = 'fichier local';

                return null;
            }

            $lien = $this->videos->reconnaitre((string) $transcription->url_origine);
            $metadonnees = $this->reessayer('metadonnees', fn () => $this->videos->metadonnees($lien));

            // Un live en cours ne se termine pas : on ne le télécharge jamais.
            if ($metadonnees->estLive) {
                throw new ErreurVideo(MotifEchec::LiveEnCours, 'live en cours');
            }

            $this->verifierDuree($transcription, $metadonnees->dureeSecondes);

            $transcription->update([
                'id_video' => $metadonnees->id,
                'titre' => Str::limit($metadonnees->titre, 500, ''),
                'chaine' => $metadonnees->chaine !== null ? Str::limit($metadonnees->chaine, 255, '') : null,
                'miniature_url' => $metadonnees->miniatureUrl,
                'duree_secondes' => $metadonnees->dureeSecondes,
                'date_metadonnees' => now(),
            ]);

            $bilan->message = $metadonnees->dureeSecondes.' s, « '.Str::limit($metadonnees->titre, 80).' »';

            return $lien;
        });
    }

    /** Téléchargement de la piste audio, ou rien pour un fichier local. Renvoie le chemin source. */
    private function etapeTelechargement(
        Transcription $transcription,
        ?string $cheminFichier,
        ?LienVideo $lien,
        ?AudioTelecharge &$audio,
    ): string {
        return $this->mesurer($transcription, EtapeTraitement::Telechargement, function (BilanEtape $bilan) use ($transcription, $cheminFichier, $lien, &$audio) {
            if ($cheminFichier !== null) {
                $transcription->update(['date_telechargement' => now()]);
                $bilan->message = 'fichier local, rien à télécharger';

                return $cheminFichier;
            }

            $audio = $this->videos->telecharger($lien);
            $transcription->update(['date_telechargement' => now()]);
            $bilan->message = 'piste audio téléchargée';

            return $audio->chemin;
        });
    }

    private function etapeMp3(Transcription $transcription, string $source, string $dossierMp3, bool $estFichier): ExtraitAudio
    {
        return $this->mesurer($transcription, EtapeTraitement::ExtractionAudio, function (BilanEtape $bilan) use ($transcription, $source, $dossierMp3, $estFichier) {
            $extrait = $this->extracteur->extraire($source, $dossierMp3, (string) $transcription->titre, $this->decoupeDuPlan($transcription));

            if ($estFichier) {
                // La durée source est connue seulement ici pour un fichier importé.
                $duree = $extrait->dureeSourceSecondes > 0 ? $extrait->dureeSourceSecondes : $extrait->dureeSecondes;

                $transcription->update(['duree_secondes' => $duree]);
                $this->verifierDuree($transcription, $duree);
            }

            $transcription->update([
                'mp3_duree_secondes' => $extrait->dureeSecondes,
                'date_extraction_audio' => now(),
            ]);
            $bilan->message = $extrait->nomFichier.', '.$extrait->dureeSecondes.' s';

            return $extrait;
        });
    }

    private function etapeTranscription(Transcription $transcription, ExtraitAudio $extrait): TranscriptionBrute
    {
        return $this->mesurer($transcription, EtapeTraitement::Transcription, function (BilanEtape $bilan) use ($transcription, $extrait) {
            $brute = $this->moteur->transcrire($extrait->chemin, $extrait->dureeSecondes, $transcription->langue_forcee);

            $transcription->update([
                'langue_detectee' => $brute->langueDetectee !== null ? mb_substr($brute->langueDetectee, 0, 8) : null,
                'nombre_mots' => count($brute->mots),
                'cout_estime_usd' => $brute->coutEstimeUsd,
                'date_transcription' => now(),
            ]);

            $bilan->message = 'langue '.($brute->langueDetectee ?? 'inconnue').', '.count($brute->mots).' mots';
            $bilan->cout = $brute->coutEstimeUsd;

            return $brute;
        });
    }

    /** @return list<Paragraphe> */
    private function etapeParagraphes(Transcription $transcription, TranscriptionBrute $brute): array
    {
        return $this->mesurer($transcription, EtapeTraitement::Paragraphes, function (BilanEtape $bilan) use ($brute) {
            $liste = $this->paragraphes->construire($brute->mots);
            $bilan->message = count($liste).' paragraphes';

            return $liste;
        });
    }

    /**
     * Stockage du MP3 puis écriture en base, dans une même transaction. Si la base
     * échoue, le MP3 déjà stocké est retiré : rien ne reste à moitié écrit.
     *
     * @param  list<Paragraphe>  $paragraphes
     */
    private function etapeEnregistrement(
        Transcription $transcription,
        ExtraitAudio $extrait,
        TranscriptionBrute $brute,
        array $paragraphes,
    ): void {
        $this->mesurer($transcription, EtapeTraitement::Enregistrement, function (BilanEtape $bilan) use ($transcription, $extrait, $brute, $paragraphes) {
            $disque = (string) config('voxia.stockage.disque');
            $chemin = $this->stocker($transcription, $extrait, $disque);

            try {
                DB::transaction(function () use ($transcription, $brute, $paragraphes, $chemin) {
                    $this->enregistrerMots($transcription, $brute->mots);
                    $this->enregistrerParagraphes($transcription, $paragraphes);

                    $transcription->update([
                        'mp3_chemin' => $chemin,
                        'statut' => StatutTranscription::Termine,
                        'date_termine' => now(),
                    ]);
                });
            } catch (Throwable $erreur) {
                Storage::disk($disque)->delete($chemin);

                throw $erreur;
            }

            $bilan->message = 'MP3 sur le disque « '.$disque.' », '.count($paragraphes).' paragraphes';
        });
    }

    /** Écrit le MP3 final sur le disque configuré, avec ses tentatives. */
    private function stocker(Transcription $transcription, ExtraitAudio $extrait, string $disque): string
    {
        return $this->reessayer('stockage', function () use ($transcription, $extrait, $disque) {
            $chemin = Storage::disk($disque)->putFileAs(
                'transcriptions/'.$transcription->getKey(),
                new File($extrait->chemin),
                $extrait->nomFichier,
            );

            if (! is_string($chemin)) {
                throw new RuntimeException('écriture refusée par le disque '.$disque);
            }

            return $chemin;
        });
    }

    /** @param  list<MotTranscrit>  $mots */
    private function enregistrerMots(Transcription $transcription, array $mots): void
    {
        TranscriptionWord::where('transcription_id', $transcription->getKey())->delete();

        $lignes = [];

        foreach (array_values($mots) as $index => $mot) {
            $lignes[] = [
                'transcription_id' => $transcription->getKey(),
                'position' => $index + 1,
                'texte' => mb_substr($mot->texte, 0, 255),
                'debut_ms' => $mot->debutMs,
                'fin_ms' => $mot->finMs,
            ];
        }

        foreach (array_chunk($lignes, 500) as $bloc) {
            TranscriptionWord::insert($bloc);
        }
    }

    /** @param  list<Paragraphe>  $paragraphes */
    private function enregistrerParagraphes(Transcription $transcription, array $paragraphes): void
    {
        TranscriptionParagraph::where('transcription_id', $transcription->getKey())->delete();

        $lignes = [];

        foreach ($paragraphes as $index => $paragraphe) {
            $lignes[] = [
                'transcription_id' => $transcription->getKey(),
                'position' => $index + 1,
                'debut_ms' => $paragraphe->debutMs,
                'fin_ms' => $paragraphe->finMs,
                'texte' => $paragraphe->texte,
            ];
        }

        foreach (array_chunk($lignes, 500) as $bloc) {
            TranscriptionParagraph::insert($bloc);
        }
    }

    /**
     * Exécute une étape, mesure sa durée et écrit sa ligne de journal. L'erreur est
     * journalisée puis relancée : c'est executer() qui passe la transcription en échec.
     *
     * @template T
     *
     * @param  Closure(BilanEtape): T  $action
     * @return T
     */
    private function mesurer(Transcription $transcription, EtapeTraitement $etape, Closure $action): mixed
    {
        $debut = hrtime(true);
        $bilan = new BilanEtape;

        try {
            $resultat = $action($bilan);
        } catch (ErreurVideo $erreur) {
            $this->journaliser($transcription, $etape, $debut, false, $erreur->getMessage(), null);

            throw $erreur;
        } catch (Throwable $erreur) {
            Log::error('traitement: erreur inattendue', ['etape' => $etape->value, 'erreur' => $erreur->getMessage()]);
            $this->journaliser($transcription, $etape, $debut, false, 'erreur inattendue : '.class_basename($erreur), null);

            throw $erreur;
        }

        $this->journaliser($transcription, $etape, $debut, true, $bilan->message, $bilan->cout);

        return $resultat;
    }

    private function journaliser(
        Transcription $transcription,
        EtapeTraitement $etape,
        int $debut,
        bool $succes,
        ?string $message,
        ?float $cout,
    ): void {
        ProcessingLog::create([
            'transcription_id' => $transcription->getKey(),
            'etape' => $etape,
            'duree_ms' => intdiv(hrtime(true) - $debut, 1_000_000),
            'succes' => $succes,
            'message' => $message !== null ? mb_substr($message, 0, 500) : null,
            'cout_usd' => $cout,
        ]);
    }

    /**
     * Relance une action pour les erreurs techniques seulement (un motif métier, comme
     * une vidéo privée, ne se retente pas). Le nombre de tentatives vient de
     * voxia.traitement.tentatives.
     *
     * @template T
     *
     * @param  Closure(): T  $action
     * @return T
     */
    private function reessayer(string $cle, Closure $action): mixed
    {
        $tentatives = max(1, (int) config('voxia.traitement.tentatives.'.$cle));

        for ($essai = 1; ; $essai++) {
            try {
                return $action();
            } catch (Throwable $erreur) {
                $reessayable = ! $erreur instanceof ErreurVideo || $erreur->estTechnique();

                if (! $reessayable || $essai >= $tentatives) {
                    throw $erreur;
                }

                Log::warning('traitement: nouvelle tentative', ['etape' => $cle, 'essai' => $essai + 1, 'sur' => $tentatives]);
                Sleep::for((int) config('voxia.traitement.delai_entre_tentatives_ms'))->milliseconds();
            }
        }
    }

    /**
     * Durée source au-delà du plan. Gratuit : la transcription se limite aux premières
     * minutes du plan (voir decoupeDuPlan) et est marquée comme aperçu, avec une invitation
     * à passer au Pro. Autres plans : refus, rien n'est transcrit.
     */
    private function verifierDuree(Transcription $transcription, int $dureeSecondes): void
    {
        $plan = $transcription->user?->plan ?? Plan::Free;
        $maximum = config('voxia.plans.'.$plan->value.'.max_video_seconds');

        if (! is_numeric($maximum) || $dureeSecondes <= (int) $maximum) {
            return;
        }

        if ($plan !== Plan::Free) {
            throw new ErreurVideo(MotifEchec::TropLongue, $dureeSecondes.' s pour un maximum de '.$maximum.' s');
        }

        $transcription->update(['est_apercu' => true]);
    }

    /** Gratuit : audio découpé à la durée du plan. Autres plans : pas de découpe. */
    private function decoupeDuPlan(Transcription $transcription): ?int
    {
        if (($transcription->user?->plan ?? Plan::Free) !== Plan::Free) {
            return null;
        }

        $maximum = config('voxia.plans.free.max_video_seconds');

        return is_numeric($maximum) ? (int) $maximum : null;
    }

    private function changerStatut(Transcription $transcription, StatutTranscription $statut): void
    {
        $transcription->update(['statut' => $statut]);
    }

    private function dossierTemporaire(): string
    {
        return rtrim((string) config('voxia.tools.download.temp_directory'), '/\\')
            .DIRECTORY_SEPARATOR.Str::uuid()->toString();
    }
}
