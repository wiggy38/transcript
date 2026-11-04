<?php

namespace App\Http\Controllers\Comptes;

use App\Http\Controllers\Controller;
use App\Jobs\ExporterDonneesCompte;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Export de toutes les données du compte (réglages, « Vos données »). La demande met l'export en file ;
 * le fichier ZIP est ensuite téléchargeable par le lien signé reçu par email, ou depuis les réglages.
 * Seul le propriétaire du compte peut télécharger son export.
 */
class DonneesController extends Controller
{
    public function demander(Request $request): RedirectResponse
    {
        ExporterDonneesCompte::dispatch($request->user()->getKey());

        return redirect()->route('reglages')->with('statut', 'export-demande');
    }

    public function telecharger(Request $request, User $utilisateur): BinaryFileResponse|RedirectResponse
    {
        abort_unless($request->user()?->is($utilisateur), 404);

        $disque = (string) config('voxia.stockage.disque');
        $stockage = Storage::disk($disque);
        $chemin = ExporterDonneesCompte::chemin($utilisateur->getKey());

        abort_unless($stockage->exists($chemin), 404);

        if (config("filesystems.disks.{$disque}.driver") === 'local') {
            return response()->download($stockage->path($chemin), 'voxia-donnees.zip', [
                'Content-Type' => 'application/zip',
                'Cache-Control' => 'private, no-store',
            ]);
        }

        return redirect()->away($stockage->temporaryUrl(
            $chemin,
            now()->addMinutes((int) config('voxia.donnees.duree_lien_minutes')),
            ['ResponseContentDisposition' => 'attachment; filename="voxia-donnees.zip"'],
        ));
    }
}
