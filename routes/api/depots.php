<?php

use App\Http\Controllers\Depot\DepotController;
use Illuminate\Support\Facades\Route;    
 
// Route::post('depots', [DepotController::class, 'store']);


// use App\Http\Controllers\Depot\DepotController;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('depots', [DepotController::class, 'store']);
});
