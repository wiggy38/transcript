<?php

use App\Mail\MessageContact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Tests\Support\CleTraduction;

uses(RefreshDatabase::class);

// Formulaire de contact (pages publiques). Un test par critère de la section « Contact » de docs/avancement.md.

beforeEach(function () {
    config(['voxia.contact.email' => 'equipe@voxia.test', 'voxia.contact.delai_min_secondes' => 3, 'voxia.contact.max_par_heure' => 5]);
    Mail::fake();
});

// Jeton d'un formulaire ouvert il y a une minute : assez ancien pour passer le délai minimal.
function jetonAncien(): string
{
    return Crypt::encryptString((string) (now()->getTimestamp() - 60));
}

function donneesContact(array $surcharge = []): array
{
    return array_merge([
        'nom' => 'Camille Dupont',
        'email' => 'camille@example.test',
        'sujet' => 'question',
        'message' => 'Bonjour, je voudrais savoir si le Pro inclut le PDF.',
        'ouvert' => jetonAncien(),
    ], $surcharge);
}

it('n\'affiche pas le formulaire tant que l\'adresse de contact n\'est pas configurée', function () {
    config(['voxia.contact.email' => null]);

    $html = test()->get('/fr/contact')->assertOk()->getContent();

    expect($html)
        ->not->toContain('name="message"')
        ->toContain('[EMAIL DE CONTACT À COMPLÉTER]');
});

it('affiche le formulaire avec son champ piège et son jeton horodaté', function () {
    $html = test()->get('/fr/contact')->assertOk()->getContent();

    expect($html)
        ->toContain('name="message"')
        ->toContain('name="site_web"')
        ->toContain('name="ouvert"');
});

it('envoie le message en file à l\'équipe, avec le visiteur pour réponse', function () {
    test()->post('/fr/contact', donneesContact())
        ->assertRedirect(route('contact', ['locale' => 'fr']))
        ->assertSessionHas('statut', 'envoye');

    Mail::assertQueued(MessageContact::class, function (MessageContact $mail) {
        return $mail->hasTo('equipe@voxia.test')
            && $mail->hasReplyTo('camille@example.test')
            && $mail->sujet === 'question';
    });
});

it('refuse un envoi trop rapide, sans rien envoyer', function () {
    test()->post('/fr/contact', donneesContact(['ouvert' => Crypt::encryptString((string) now()->getTimestamp())]))
        ->assertSessionHasErrors('formulaire');

    Mail::assertNotQueued(MessageContact::class);
});

it('refuse un jeton altéré, sans rien envoyer', function () {
    test()->post('/fr/contact', donneesContact(['ouvert' => 'n-importe-quoi']))
        ->assertSessionHasErrors('formulaire');

    Mail::assertNotQueued(MessageContact::class);
});

it('ignore sans erreur visible un message dont le champ piège est rempli', function () {
    test()->post('/fr/contact', donneesContact(['site_web' => 'https://spam.example.test']))
        ->assertRedirect(route('contact', ['locale' => 'fr']))
        ->assertSessionHas('statut', 'envoye')
        ->assertSessionMissing('errors');

    Mail::assertNotQueued(MessageContact::class);
});

it('limite le nombre de messages acceptés par adresse IP', function () {
    config(['voxia.contact.max_par_heure' => 2]);

    test()->post('/fr/contact', donneesContact())->assertSessionHas('statut', 'envoye');
    test()->post('/fr/contact', donneesContact())->assertSessionHas('statut', 'envoye');
    test()->post('/fr/contact', donneesContact())->assertSessionHasErrors('formulaire');

    Mail::assertQueued(MessageContact::class, 2);
});

it('refuse un message incomplet et ne l\'envoie pas', function () {
    test()->post('/fr/contact', donneesContact(['message' => 'court', 'email' => 'pas-un-email']))
        ->assertSessionHasErrors(['message', 'email']);

    Mail::assertNotQueued(MessageContact::class);
});

it('prépare le formulaire dans les deux langues sans clé visible', function (string $langue) {
    $html = test()->get("/{$langue}/contact")->assertOk()->getContent();

    expect($html)->toContain('<html lang="'.$langue.'"');
    expect(CleTraduction::visible($html))->toBeNull();
})->with(['fr', 'en']);
