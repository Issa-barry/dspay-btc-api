<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\DeleteUserController;
use App\Http\Controllers\User\ShowUserController;
use App\Http\Controllers\User\updateUserController;
use App\Http\Controllers\User\UserStatutController;
use App\Http\Controllers\User\UserAffecterAgenceController;
use App\Http\Controllers\User\UserDesacfecterAgenceController;
use App\Http\Controllers\User\Employe\EmployeCreateController;
use App\Http\Controllers\User\Client\ClientCreateController;

Route::prefix('users')->name('users.')->group(function () {
    // ── PUBLIC : création de compte client (pas d'auth)
    Route::post('/clients/create', [ClientCreateController::class, 'store'])
        ->name('clients.store')
        ->middleware('throttle:20,1'); // anti-spam 

    // ── PROTÉGÉ : tout le reste
    Route::middleware(['auth:sanctum','throttle:60,1'])->group(function () {
        Route::post('/employes/create', [EmployeCreateController::class, 'store'])->name('employes.store');

        Route::get('/all', [ShowUserController::class, 'index'])->name('index');
        Route::get('/getById/{id}', [ShowUserController::class, 'getById'])->name('show')->whereNumber('id');
        Route::put('/updateById/{id}', [updateUserController::class, 'updateById'])->name('update')->whereNumber('id');
        Route::delete('/deleteById/{id}', [DeleteUserController::class, 'delateById'])->name('delete')->whereNumber('id');

        Route::patch('/{id}/statutUpdate', [UserStatutController::class, 'updateStatut'])->name('statut.update')->whereNumber('id');

        Route::post('/affecterByReference/{id}', [UserAffecterAgenceController::class, 'affecterParReferenceAgence'])->name('affect.ref')->whereNumber('id');
        Route::post('/affecter-agence/{id}', [UserAffecterAgenceController::class, 'affecterAgence'])->name('affect')->whereNumber('id');
        Route::delete('/desaffecter-agence/{id}', [UserDesacfecterAgenceController::class, 'desaffecterAgence'])->name('desaffect')->whereNumber('id');
    });
});
