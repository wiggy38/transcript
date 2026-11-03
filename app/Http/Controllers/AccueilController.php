<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class AccueilController extends Controller
{
    /** Page d'accueil : le champ de lien démarre le parcours principal (cahier §2). */
    public function __invoke(Request $request): View
    {
        return view('accueil');
    }
}
