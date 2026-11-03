<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Guide de style : visible seulement en local, jamais en production.
if (app()->isLocal()) {
    Route::view('/styleguide', 'styleguide')->name('styleguide');
}
