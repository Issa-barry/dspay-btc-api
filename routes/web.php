<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use Illuminate\Support\Facades\Auth;

// Auth::routes(['verify' => true]);

use App\Models\Transfert;
use App\Mail\TransfertNotification;
 

Route::get('/preview-email', function () {
    $transfert = \App\Models\Transfert::with(['deviseSource', 'deviseCible'])->first(); // Exemple avec données réelles

    return view('emails.transfertNotification', ['transfert' => $transfert]);
});

Route::get('/preview-email-retrait', function () {
    $transfert = \App\Models\Transfert::with(['deviseSource', 'deviseCible'])->first(); // Exemple avec données réelles

    return view('emails.transfertRetire', ['transfert' => $transfert]);
});
