<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Devises\{
    DeviseCreateController,
    DeviseDeleteController,
    DeviseShowController,
    DeviseUpdateController
};

Route::middleware(['auth:sanctum', 'throttle:60,1'])
    ->prefix('devises')->name('devises.')
    ->group(function () {
        Route::post('/create', [DeviseCreateController::class, 'store'])->name('store');
        Route::get('/all', [DeviseShowController::class, 'index'])->name('index');
        Route::get('/getById/{id}', [DeviseShowController::class, 'getById'])->name('show');
        Route::put('/updateById/{id}', [DeviseUpdateController::class, 'updateById'])->name('update');
        Route::delete('/deleteById/{id}', [DeviseDeleteController::class, 'deleteById'])->name('delete');
    });
