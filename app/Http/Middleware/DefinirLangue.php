<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applique la langue de l'interface : celle du compte connecté, sinon la langue par défaut
 * de l'application. Une valeur hors de voxia.locales est ignorée.
 */
class DefinirLangue
{
    public function handle(Request $request, Closure $next): Response
    {
        $langue = $request->user()?->locale;

        if (is_string($langue) && in_array($langue, (array) config('voxia.locales'), true)) {
            app()->setLocale($langue);
        }

        return $next($request);
    }
}
