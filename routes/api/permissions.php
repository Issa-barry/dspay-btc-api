<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Permissions\PermissionController;

Route::middleware(['auth:sanctum', 'throttle:60,1'])
    ->prefix('permissions')->name('permissions.')
    ->group(function () {
        Route::get('/', [PermissionController::class, 'index'])->name('index');
        Route::post('/', [PermissionController::class, 'create'])->name('store');
        Route::get('/{id}', [PermissionController::class, 'show'])->name('show');
        Route::put('/{id}', [PermissionController::class, 'update'])->name('update');
        Route::delete('/{id}', [PermissionController::class, 'destroy'])->name('delete');
    });
