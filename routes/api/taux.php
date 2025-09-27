<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Taux\{
    TauxCreateController,
    TauxDeleteController,
    TauxShowController,
    TauxUpdateController
};

Route::middleware(['auth:sanctum', 'throttle:60,1'])
    ->prefix('taux')->name('taux.')
    ->group(function () {
        Route::get('/all', [TauxShowController::class, 'index'])->name('index');
        Route::get('/getById/{id}', [TauxShowController::class, 'getById'])->name('show');
        Route::put('/updateById/{id}', [TauxUpdateController::class, 'updateById'])->name('update');
        Route::delete('/deleteById/{id}', [TauxDeleteController::class, 'deleteById'])->name('delete');
        Route::post('/createById', [TauxCreateController::class, 'createById'])->name('storeById');
        Route::post('/createByName', [TauxCreateController::class, 'storeByName'])->name('storeByName');
    });
