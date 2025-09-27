<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Agence\{
    AgenceCreateController,
    AgenceDeleteController,
    AgenceShowController,
    AgenceUpdateController,
    AgenceStatutController
};

Route::middleware(['auth:sanctum'])
    ->prefix('agences')->name('agences.')
    ->group(function () {
        Route::post('/create', [AgenceCreateController::class, 'store'])->name('store');
        Route::get('/all', [AgenceShowController::class, 'index'])->name('index');
        Route::get('/getById/{id}', [AgenceShowController::class, 'show'])->name('show');
        Route::get('/getByReference/{reference}', [AgenceShowController::class, 'showByReference'])->name('showByRef');
        Route::put('/updateById/{id}', [AgenceUpdateController::class, 'updateById'])->name('update');
        Route::delete('/deleteById/{id}', [AgenceDeleteController::class, 'deleteById'])->name('delete');
        Route::patch('/{id}/statutUpdate', [AgenceStatutController::class, 'updateStatut'])->name('statut.update');
    });
