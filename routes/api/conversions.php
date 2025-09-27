<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ConversionController;

Route::middleware(['auth:sanctum', 'throttle:60,1'])
    ->prefix('conversions')->name('conversions.')
    ->controller(ConversionController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{conversion}', 'show')->name('show');
        Route::put('/{conversion}', 'update')->name('update');
        Route::delete('/{conversion}', 'destroy')->name('delete');
    });
