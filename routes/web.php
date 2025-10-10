<?php

use Illuminate\Support\Facades\Route;

/**
 * Tout fichier ici passe par le middleware "web"
 * (cookies, session, CSRF, etc.)
 */

// Page welcome (optionnelle)
Route::get('/', fn () => view('welcome'));

 
 