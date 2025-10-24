<?php

use App\Http\Controllers\Agence\AgenceCreateController;
use App\Http\Controllers\Agence\AgenceDeleteByReferenceController;
use App\Http\Controllers\Agence\AgenceIndexController;
use App\Http\Controllers\Agence\AgenceShowByReferenceController;
use App\Http\Controllers\Agence\AgenceUpdateByReferenceController;
use Illuminate\Support\Facades\Route;
 

Route::middleware(['auth:sanctum','throttle:60,1'])
    ->prefix('agences')->name('agences.')
    ->group(function () {
        Route::post('create', [AgenceCreateController::class, 'store'])->name('store');
        Route::get('all', [AgenceIndexController::class, 'index'])->name('index');
        Route::get('getByReference/{reference}', [AgenceShowByReferenceController::class, 'getByReference'])->name('getByReference');
        Route::put('updateReference/{reference}', [AgenceUpdateByReferenceController::class, 'updateReference'])->name('updateReference');
        Route::delete('deleteByReference/{reference}', [AgenceDeleteByReferenceController::class, 'deleteByReference'])->name('deleteByReference');
    });
 

    
 