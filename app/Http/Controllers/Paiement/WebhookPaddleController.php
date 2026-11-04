<?php

namespace App\Http\Controllers\Paiement;

use App\Services\Paiement\RecevoirEvenementPaiement;
use Illuminate\Http\Request;
use Laravel\Paddle\Http\Controllers\WebhookController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Réception des webhooks Paddle. Cashier vérifie la signature (middleware VerifyWebhookSignature,
 * appliqué à la construction du contrôleur) et met à jour ses propres tables. Ici, l'événement est
 * enregistré une fois, puis son traitement part en file.
 *
 * Sans secret configuré, aucun webhook n'est accepté : la signature ne pourrait pas être vérifiée.
 */
class WebhookPaddleController extends WebhookController
{
    public function __invoke(Request $request): Response
    {
        if (blank(config('cashier.webhook_secret'))) {
            abort(403, 'Secret des webhooks absent : réception désactivée.');
        }

        $reponse = parent::__invoke($request);

        app(RecevoirEvenementPaiement::class)->enregistrer($request->all());

        return $reponse;
    }
}
