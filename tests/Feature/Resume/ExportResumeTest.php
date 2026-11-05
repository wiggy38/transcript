<?php

require_once __DIR__.'/outils.php';

use App\Enums\Plan;
use App\Jobs\GenererResume;
use App\Models\Transcription;
use App\Models\User;
use App\Services\Resume\LancerResume;
use App\Services\Resume\ServiceResume;
use App\Support\Horodatage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

beforeEach(fn () => Queue::fake());

/** Transcription dont le résumé est terminé : base des tests d'export. */
function resumeTermine(User $utilisateur): Transcription
{
    $transcription = resumeTranscriptionCourte($utilisateur);
    resumeFaux()->repondre(resumeReponse(resumeSortieValide()));
    $sortie = app(LancerResume::class)->lancer($utilisateur, $transcription);
    (new GenererResume($sortie->getKey()))->handle(app(ServiceResume::class));

    return $transcription;
}

function resumeLienSigne(Transcription $transcription, string $format): string
{
    return URL::temporarySignedRoute('transcriptions.resume.export', now()->addMinutes(10), [$transcription, $format]);
}

// Critère export : le TXT reprend le résumé, les points et leurs horodatages (ceux de la transcription).
it('exporte le résumé en TXT avec les horodatages de la transcription', function () {
    $utilisateur = resumeUtilisateur(Plan::Pro);
    $transcription = resumeTermine($utilisateur);

    $reponse = $this->actingAs($utilisateur)->get(resumeLienSigne($transcription, 'txt'));

    $reponse->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8');

    expect($reponse->getContent())
        ->toContain('Le balcon sud reçoit beaucoup de soleil.')
        ->toContain('['.Horodatage::format(20000).'] Choisir un balcon bien orienté.');
});

// Critère export : le PDF est produit avec le même contenu.
it('exporte le résumé en PDF', function () {
    $utilisateur = resumeUtilisateur(Plan::Pro);
    $transcription = resumeTermine($utilisateur);

    $reponse = $this->actingAs($utilisateur)->get(resumeLienSigne($transcription, 'pdf'));

    $reponse->assertOk()->assertHeader('Content-Type', 'application/pdf');
    expect(substr($reponse->getContent(), 0, 4))->toBe('%PDF');
});

// Critère plan : le DOCX est réservé au Business.
it('réserve le DOCX au plan Business', function () {
    $pro = resumeUtilisateur(Plan::Pro);
    $transcriptionPro = resumeTermine($pro);
    $this->actingAs($pro)->get(resumeLienSigne($transcriptionPro, 'docx'))->assertForbidden();

    $business = resumeUtilisateur(Plan::Business);
    $transcriptionBusiness = resumeTermine($business);
    $reponse = $this->actingAs($business)->get(resumeLienSigne($transcriptionBusiness, 'docx'));

    $reponse->assertOk();
    expect(substr($reponse->getContent(), 0, 2))->toBe('PK');
});

// Critère accès : un lien non signé est refusé.
it('refuse un export sans signature valide', function () {
    $utilisateur = resumeUtilisateur(Plan::Pro);
    $transcription = resumeTermine($utilisateur);

    $this->actingAs($utilisateur)
        ->get('/transcriptions/'.$transcription->getKey().'/resume/txt')
        ->assertForbidden();
});

// Critère accès : le résumé d'un autre compte répond 404, même avec un lien signé.
it('répond 404 pour le résumé d’un autre compte', function () {
    $proprietaire = resumeUtilisateur(Plan::Pro);
    $transcription = resumeTermine($proprietaire);
    $autre = resumeUtilisateur(Plan::Pro);

    $this->actingAs($autre)->get(resumeLienSigne($transcription, 'txt'))->assertNotFound();
});
