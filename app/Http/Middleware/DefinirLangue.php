<?php

namespace App\Http\Middleware;

use App\Support\Langue;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applique la langue de l'interface (voir App\Support\Langue::resoudre) et la donne à toutes les
 * adresses générées (route('accueil') suit la langue courante). Sur une page à adresse par
 * langue (/fr, /en), le choix est mémorisé dans un cookie : les requêtes Livewire et les
 * écrans suivants restent dans la même langue.
 */
class DefinirLangue
{
    public function handle(Request $request, Closure $next): Response
    {
        $langue = Langue::resoudre($request);

        app()->setLocale($langue);
        URL::defaults(['locale' => $langue]);

        $reponse = $next($request);

        if ($request->route('locale') !== null) {
            $reponse->headers->setCookie(Langue::cookie($langue));
        }

        return $reponse;
    }
}
