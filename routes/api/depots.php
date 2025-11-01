<?php

use App\Http\Controllers\Depot\DepotController;
use Illuminate\Support\Facades\Route;    
 
Route::post('depots', [DepotController::class, 'store']);