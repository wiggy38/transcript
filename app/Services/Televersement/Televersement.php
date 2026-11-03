<?php

namespace App\Services\Televersement;

use App\Models\User;
use App\Services\Audio\NomFichierAudio;
use Illuminate\Http\UploadedFile;
use RuntimeException;

/**
 * Réception d'un fichier envoyé en morceaux (cahier F1, 2 Go).
 *
 * Chaque morceau est écrit dans un dossier propre à l'utilisateur et à l'envoi. Le dernier
 * morceau déclenche l'assemblage : le fichier final porte un nom nettoyé (NomFichierAudio),
 * sa taille est contrôlée, puis les morceaux sont supprimés. Un envoi interrompu ne laisse
 * qu'un dossier de morceaux, relancé depuis le début avec un nouvel identifiant.
 */
final class Televersement
{
    private const MOTIF_ENVOI = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/';

    public static function estIdentifiantEnvoi(string $envoi): bool
    {
        return preg_match(self::MOTIF_ENVOI, $envoi) === 1;
    }

    public static function extensionAcceptee(string $nom): bool
    {
        $extension = strtolower(pathinfo($nom, PATHINFO_EXTENSION));

        return in_array($extension, (array) config('voxia.uploads.extensions'), true);
    }

    public static function tailleMaxOctets(): int
    {
        return (int) config('voxia.uploads.max_size_mb') * 1024 * 1024;
    }

    /** Nombre de morceaux attendus pour une taille donnée. */
    public static function nombreMorceaux(int $taille): int
    {
        return max(1, (int) ceil($taille / (int) config('voxia.uploads.morceau_octets')));
    }

    /**
     * Écrit un morceau. Renvoie le nom final du fichier une fois le dernier morceau reçu
     * et l'assemblage terminé, sinon null.
     *
     * @throws RuntimeException envoi incomplet ou taille finale fausse
     */
    public function recevoirMorceau(User $utilisateur, string $envoi, int $index, int $total, int $taille, string $nomOriginal, UploadedFile $morceau): ?string
    {
        $dossier = $this->dossierEnvoi($utilisateur, $envoi);

        if (! is_dir($dossier)) {
            mkdir($dossier, 0775, true);
        }

        $morceau->move($dossier, $index.'.part');

        if ($index < $total - 1) {
            return null;
        }

        return $this->assembler($dossier, $total, $taille, $nomOriginal);
    }

    /** Chemin d'un fichier reçu, s'il appartient à l'utilisateur. Nul sinon. */
    public function cheminFichier(User $utilisateur, string $envoi, string $nom): ?string
    {
        if (! self::estIdentifiantEnvoi($envoi) || basename($nom) !== $nom || ! self::extensionAcceptee($nom)) {
            return null;
        }

        $chemin = $this->dossierEnvoi($utilisateur, $envoi).DIRECTORY_SEPARATOR.$nom;

        return is_file($chemin) ? $chemin : null;
    }

    private function assembler(string $dossier, int $total, int $taille, string $nomOriginal): string
    {
        for ($i = 0; $i < $total; $i++) {
            if (! is_file($dossier.DIRECTORY_SEPARATOR.$i.'.part')) {
                throw new RuntimeException('envoi incomplet');
            }
        }

        $extension = strtolower(pathinfo($nomOriginal, PATHINFO_EXTENSION));
        $nom = NomFichierAudio::depuisTitre(pathinfo($nomOriginal, PATHINFO_FILENAME), $extension);
        $final = $dossier.DIRECTORY_SEPARATOR.$nom;

        $sortie = fopen($final, 'wb');

        try {
            for ($i = 0; $i < $total; $i++) {
                $entree = fopen($dossier.DIRECTORY_SEPARATOR.$i.'.part', 'rb');
                stream_copy_to_stream($entree, $sortie);
                fclose($entree);
            }
        } finally {
            fclose($sortie);
        }

        if (filesize($final) !== $taille) {
            unlink($final);

            throw new RuntimeException('taille finale incorrecte');
        }

        for ($i = 0; $i < $total; $i++) {
            unlink($dossier.DIRECTORY_SEPARATOR.$i.'.part');
        }

        return $nom;
    }

    private function dossierEnvoi(User $utilisateur, string $envoi): string
    {
        return rtrim((string) config('voxia.uploads.dossier'), '/\\')
            .DIRECTORY_SEPARATOR.$utilisateur->getKey()
            .DIRECTORY_SEPARATOR.$envoi;
    }
}
