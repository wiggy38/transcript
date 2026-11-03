<?php

namespace App\Services\Transcription;

use App\Contracts\TranscriptionEngine;
use App\Data\TranscriptionBrute;
use App\Enums\MotifEchec;
use App\Exceptions\ErreurVideo;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;
use UnexpectedValueException;

/**
 * Transcription avec ElevenLabs Scribe v2 (POST /v1/speech-to-text, multipart).
 *
 * Chaque échec d'appel (clé, quota, taille, délai, réseau, serveur, réponse
 * illisible) est retenté jusqu'à voxia.transcription.attempts fois, puis devient
 * une ErreurVideo « erreur_technique ». Le motif réel reste dans les journaux.
 * Une limite locale (taille, durée, clé absente) échoue sans aucun appel.
 */
final class ElevenLabsScribeEngine implements TranscriptionEngine
{
    public function transcrire(
        string $cheminAudio,
        int $dureeSecondes,
        ?string $langueForcee = null,
    ): TranscriptionBrute {
        $cle = (string) config('services.elevenlabs.key');

        if ($cle === '') {
            Log::error('elevenlabs: clé d’API absente de la configuration');

            throw new ErreurVideo(MotifEchec::ErreurTechnique, 'clé absente');
        }

        if (! is_file($cheminAudio) || ! is_readable($cheminAudio)) {
            throw new ErreurVideo(MotifEchec::ErreurTechnique, 'fichier audio introuvable');
        }

        $tailleMax = (int) config('voxia.transcription.max_size_mb') * 1024 * 1024;

        if ((int) filesize($cheminAudio) > $tailleMax) {
            throw new ErreurVideo(MotifEchec::ErreurTechnique, 'fichier trop volumineux');
        }

        if ($dureeSecondes > (int) config('voxia.transcription.max_duration_seconds')) {
            throw new ErreurVideo(MotifEchec::ErreurTechnique, 'durée trop longue');
        }

        $tentatives = max(1, (int) config('voxia.transcription.attempts'));

        for ($essai = 1; ; $essai++) {
            try {
                return $this->appeler($cle, $cheminAudio, $dureeSecondes, $langueForcee);
            } catch (EchecScribe $echec) {
                Log::warning('elevenlabs: échec de transcription', [
                    'essai' => $essai,
                    'sur' => $tentatives,
                    'motif' => $echec->motif,
                    'statut' => $echec->statut,
                    'corps' => $echec->corps,
                ]);

                if ($essai >= $tentatives) {
                    throw new ErreurVideo(MotifEchec::ErreurTechnique, $echec->motif);
                }

                Sleep::for((int) config('voxia.transcription.retry_delay_ms'))->milliseconds();
            }
        }
    }

    /** @throws EchecScribe */
    private function appeler(
        string $cle,
        string $cheminAudio,
        int $dureeSecondes,
        ?string $langueForcee,
    ): TranscriptionBrute {
        $flux = fopen($cheminAudio, 'rb');

        if ($flux === false) {
            throw new EchecScribe('fichier illisible');
        }

        try {
            $reponse = Http::withHeaders(['xi-api-key' => $cle])
                ->acceptJson()
                ->connectTimeout((int) config('voxia.transcription.connect_timeout_seconds'))
                ->timeout((int) config('voxia.transcription.timeout_seconds'))
                ->attach('file', $flux, basename($cheminAudio))
                ->post($this->adresse(), $this->champs($langueForcee));
        } catch (ConnectionException $exception) {
            $delai = str_contains(strtolower($exception->getMessage()), 'timed out')
                || str_contains(strtolower($exception->getMessage()), 'timeout');

            throw new EchecScribe($delai ? 'délai dépassé' : 'connexion impossible');
        } finally {
            fclose($flux);
        }

        if ($reponse->successful()) {
            return $this->lire($reponse, $dureeSecondes);
        }

        throw new EchecScribe(
            $this->motifHttp($reponse),
            $reponse->status(),
            mb_substr($reponse->body(), 0, (int) config('voxia.transcription.error_body_log_chars')),
        );
    }

    /** @throws EchecScribe */
    private function lire(Response $reponse, int $dureeSecondes): TranscriptionBrute
    {
        $json = $reponse->json();

        if (! is_array($json)) {
            throw new EchecScribe('réponse illisible', $reponse->status());
        }

        try {
            return ReponseElevenLabs::versTranscription($json, $dureeSecondes);
        } catch (UnexpectedValueException $exception) {
            throw new EchecScribe('réponse illisible : '.$exception->getMessage(), $reponse->status());
        }
    }

    /** Motif lisible à partir du statut HTTP et de detail.status, sans jamais lire la clé. */
    private function motifHttp(Response $reponse): string
    {
        $detail = (string) $reponse->json('detail.status');

        return match (true) {
            str_contains($detail, 'quota'), $reponse->status() === 402 => 'quota dépassé',
            $reponse->status() === 401, $reponse->status() === 403 => 'clé d’API invalide',
            $reponse->status() === 413 => 'fichier trop volumineux',
            $reponse->status() === 422 => 'requête refusée',
            $reponse->status() === 429 => 'limite de débit atteinte',
            $reponse->status() >= 500 => 'service indisponible',
            default => 'erreur HTTP '.$reponse->status(),
        };
    }

    /** @return array<string, string> */
    private function champs(?string $langueForcee): array
    {
        $champs = [
            'model_id' => (string) config('services.elevenlabs.model'),
            'timestamps_granularity' => 'word',
        ];

        if ($langueForcee !== null && $langueForcee !== '') {
            $champs['language_code'] = $langueForcee;
        }

        return $champs;
    }

    private function adresse(): string
    {
        return rtrim((string) config('services.elevenlabs.base_url'), '/').'/v1/speech-to-text';
    }
}
