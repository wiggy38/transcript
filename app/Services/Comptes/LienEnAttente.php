<?php

namespace App\Services\Comptes;

use App\Exceptions\ErreurVideo;
use App\Models\Transcription;
use App\Models\User;
use App\Services\Traitement\LancerTranscription;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * Lien collé par un visiteur avant d'avoir un compte. Il est gardé en session tant que
 * le visiteur n'est pas connecté, puis reporté sur le compte à la connexion (ou à
 * l'inscription), et repris à l'arrivée dans l'application une fois l'email vérifié.
 */
final class LienEnAttente
{
    public const CLE_SESSION = 'lien_en_attente';

    /** Garde le lien en session (visiteur non connecté). */
    public static function memoriserEnSession(Request $request, string $lien): void
    {
        $request->session()->put(self::CLE_SESSION, $lien);
    }

    /** Reporte le lien de la session vers le compte, s'il y en a un. Appelé à la connexion. */
    public static function reporterVersCompte(Request $request, User $utilisateur): void
    {
        $lien = $request->session()->pull(self::CLE_SESSION);

        if (is_string($lien) && $lien !== '') {
            $utilisateur->forceFill(['lien_en_attente' => $lien])->save();
        }
    }

    /**
     * Lance la transcription du lien en attente si l'email est vérifié. Le lien est retiré
     * du compte dans tous les cas, pour ne jamais le relancer deux fois.
     *
     * @return Transcription|null null si rien n'était en attente ou si le lancement a échoué
     */
    public static function reprendre(User $utilisateur): ?Transcription
    {
        if ($utilisateur->lien_en_attente === null || ! $utilisateur->hasVerifiedEmail()) {
            return null;
        }

        $lien = $utilisateur->lien_en_attente;
        $utilisateur->forceFill(['lien_en_attente' => null])->save();

        try {
            return app(LancerTranscription::class)->lancer($utilisateur, $lien);
        } catch (ErreurVideo|InvalidArgumentException) {
            return null;
        }
    }
}
