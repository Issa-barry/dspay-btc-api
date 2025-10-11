<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Beneficiaire\{
    BeneficiaireDeleteController,
    BeneficiaireIndexController,
    BeneficiaireStoreController,
    BeneficiaireUpdateController
};

Route::middleware(['auth:sanctum','throttle:60,1'])
    ->prefix('beneficiaires')->name('beneficiaires.')
    ->group(function () {
        Route::get('all', [BeneficiaireIndexController::class, 'index'])->name('index');
        Route::get('getById/{id}', [BeneficiaireIndexController::class, 'getById'])->name('show');
        Route::post('create', [BeneficiaireStoreController::class, 'store'])->name('store');
        Route::put('updateById/{id}', [BeneficiaireUpdateController::class, 'updateById'])->name('update');
        Route::delete('deleteById/{id}', [BeneficiaireDeleteController::class, 'deleteById'])->name('delete');
    });
 