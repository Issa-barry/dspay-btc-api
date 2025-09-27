<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Transfert\{
    TransfertAnnulerController,
    TransfertDeleteController,
    TransfertEnvoieController,
    TransfertRetraitController,
    TransfertShowController,
    TransfertUpdateController,
    TransfertStatistiqueController
};

Route::middleware(['auth:sanctum', 'throttle:60,1'])
    ->prefix('transferts')->name('transferts.')
    ->group(function () {
        // actions
        Route::post('/envoie', [TransfertEnvoieController::class, 'store'])->name('envoie');
        Route::post('/annuler/{id}', [TransfertAnnulerController::class, 'annulerTransfert'])
            ->name('annuler')->whereNumber('id');
        Route::post('/retrait', [TransfertRetraitController::class, 'validerRetrait'])->name('retrait');

        // lecture
        Route::get('/all', [TransfertShowController::class, 'index'])->name('index');
        Route::get('/showById/{id}', [TransfertShowController::class, 'show'])
            ->name('show')->whereNumber('id');
        Route::get('/showByCode/{code}', [TransfertShowController::class, 'showByCode'])
            ->name('showByCode')->where('code', '[A-Za-z0-9\-]+');
        Route::get('/by-user', [TransfertShowController::class, 'byUser'])->name('byUser');

        // update
        Route::put('/updateById/{id}', [TransfertUpdateController::class, 'updateById'])
            ->name('updateById')->whereNumber('id');
        Route::put('/updateByCode/{code}', [TransfertUpdateController::class, 'updateByCode'])
            ->name('updateByCode')->where('code', '[A-Za-z0-9\-]+');

        // delete
        Route::delete('/deleteById/{id}', [TransfertDeleteController::class, 'deleteById'])
            ->name('deleteById')->whereNumber('id');
        Route::delete('/deleteByCode/{code}', [TransfertDeleteController::class, 'deleteByCode'])
            ->name('deleteByCode')->where('code', '[A-Za-z0-9\-]+');

        // stats
        Route::get('/statistiques/agence/{agenceId}', [TransfertStatistiqueController::class, 'getSommeTransfertsParAgence'])
            ->name('stats.agence')->whereNumber('agenceId');
        Route::get('/statistiques/globales', [TransfertStatistiqueController::class, 'getStatistiquesGlobales'])
            ->name('stats.globales');
    });
