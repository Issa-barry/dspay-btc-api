<?php

use App\Http\Controllers\Auth\LoginCookieController;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Controllers\CsrfCookieController;


Route::get('/', fn () => view('welcome'));

 // 1) Cookie XSRF + session (DOIT être sous "web")
Route::get('/sanctum/csrf-cookie', [CsrfCookieController::class, 'show'])->middleware('web');

// 2) Login/Logout "session-based"
Route::prefix('web')->middleware('web')->group(function () {
    Route::post('/login',  LoginCookieController::class)->middleware('guest')->name('web.login');
    //  Route::get('/login2',  [LoginCookieController::class, 'index'])->name('web.login2');
});