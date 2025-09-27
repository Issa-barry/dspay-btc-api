<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Frais\{
    FraisCreateController,
    FraisDeleteController,
    FraisShowController,
    FraisUpdateController
};

Route::middleware(['auth:sanctum', 'throttle:60,1'])
    ->prefix('frais')->name('frais.')
    ->group(function () {
        Route::get('/all', [FraisShowController::class, 'index'])->name('index');
        Route::get('/getById/{id}', [FraisShowController::class, 'show'])->name('show');
        Route::post('/create', [FraisCreateController::class, 'create'])->name('store');
        Route::put('/updateById/{id}', [FraisUpdateController::class, 'updateById'])->name('update');
        Route::delete('/deleteById/{id}', [FraisDeleteController::class, 'deleteById'])->name('delete');
    });
