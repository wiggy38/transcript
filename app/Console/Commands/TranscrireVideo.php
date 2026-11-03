<?php

namespace App\Console\Commands;

use App\Enums\StatutTranscription;
use App\Exceptions\ErreurVideo;
use App\Models\ProcessingLog;
use App\Models\Transcription;
use App\Models\User;
use App\Services\Traitement\LancerTranscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Sleep;
use InvalidArgumentException;

/**
 * Lance une transcription depuis la ligne de commande, suit sa progression et affiche
 * les 10 premiers paragraphes. Outil d'exploitation et de test : la version web
 * n'attend jamais le traitement.
 */
class TranscrireVideo extends Command
{
    protected $signature = 'voxia:transcribe
        {source : Lien YouTube ou TikTok, ou chemin d’un fichier local}
        {--user= : E-mail de l’utilisateur à qui rattacher la transcription}
        {--langue= : Code de langue à forcer (ex. fr, en) ; sinon détection automatique}';

    protected $description = 'Lance la transcription d’un lien ou d’un fichier, suit la progression et affiche les 10 premiers paragraphes';

    public function handle(LancerTranscription $lancement): int
    {
        $email = (string) $this->option('user');

        if ($email === '') {
            $this->error(__('traitement.utilisateur_requis'));

            return self::FAILURE;
        }

        $utilisateur = User::where('email', $email)->first();

        if ($utilisateur === null) {
            $this->error(__('traitement.utilisateur_introuvable'));

            return self::FAILURE;
        }

        try {
            $transcription = $lancement->lancer(
                $utilisateur,
                (string) $this->argument('source'),
                $this->option('langue') ?: null,
            );
        } catch (ErreurVideo $erreur) {
            $this->error($erreur->messageUtilisateur());

            return self::FAILURE;
        } catch (InvalidArgumentException) {
            $this->error(__('traitement.fichier_introuvable'));

            return self::FAILURE;
        }

        $this->info(__('traitement.lancee', ['id' => $transcription->getKey()]));

        return $this->suivre($transcription);
    }

    private function suivre(Transcription $transcription): int
    {
        $dernierLog = 0;
        $dernierStatut = null;
        $limite = now()->addSeconds((int) config('voxia.traitement.suivi_delai_secondes'));

        while (true) {
            $transcription->refresh();

            $nouveaux = ProcessingLog::where('transcription_id', $transcription->getKey())
                ->where('id', '>', $dernierLog)
                ->orderBy('id')
                ->get();

            foreach ($nouveaux as $ligne) {
                $dernierLog = $ligne->getKey();
                $this->line($this->ligneJournal($ligne));
            }

            if ($transcription->statut !== $dernierStatut) {
                $dernierStatut = $transcription->statut;
                $this->comment(__('traitement.statut', ['statut' => __('traitement.statuts.'.$dernierStatut->value)]));
            }

            if (in_array($transcription->statut, [StatutTranscription::Termine, StatutTranscription::Echec], true)) {
                break;
            }

            if (now()->greaterThan($limite)) {
                $this->error(__('traitement.delai_depasse'));

                return self::FAILURE;
            }

            Sleep::for((int) config('voxia.traitement.suivi_intervalle_secondes'))->seconds();
        }

        if ($transcription->statut === StatutTranscription::Echec) {
            $this->error(__('traitement.echec', [
                'motif' => __('videos.motifs.'.$transcription->motif_echec?->value),
            ]));

            return self::FAILURE;
        }

        $this->afficherParagraphes($transcription);

        return self::SUCCESS;
    }

    private function ligneJournal(ProcessingLog $ligne): string
    {
        $icone = $ligne->succes ? '✔' : '✘';
        $details = [$ligne->duree_ms.' ms'];

        if ($ligne->cout_usd !== null) {
            $details[] = number_format((float) $ligne->cout_usd, 4, '.', '').' $';
        }

        if ($ligne->message !== null) {
            $details[] = $ligne->message;
        }

        return sprintf(
            '  %s %s (%s)',
            $icone,
            __('traitement.etapes.'.$ligne->etape->value),
            implode(' · ', $details),
        );
    }

    private function afficherParagraphes(Transcription $transcription): void
    {
        $total = $transcription->paragraphs()->count();

        $this->info(__('traitement.paragraphes_total', ['total' => $total]));

        foreach ($transcription->paragraphs()->limit(10)->get() as $paragraphe) {
            $this->line(sprintf('[%s] %s', $this->horodatage($paragraphe->debut_ms), $paragraphe->texte));
        }
    }

    private function horodatage(int $millisecondes): string
    {
        $secondes = intdiv($millisecondes, 1000);
        $heures = intdiv($secondes, 3600);
        $minutes = intdiv($secondes % 3600, 60);
        $reste = $secondes % 60;

        return $heures > 0
            ? sprintf('%02d:%02d:%02d', $heures, $minutes, $reste)
            : sprintf('%02d:%02d', $minutes, $reste);
    }
}
