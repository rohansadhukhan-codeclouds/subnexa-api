<?php

use App\Http\Controllers\Api\Admin\HomeController;
use App\Http\Controllers\Api\Admin\LoginController;
use App\Http\Controllers\Api\Admin\LogoutController;
use App\Http\Controllers\Api\Admin\MeController;
use Illuminate\Support\Facades\Route;

Route::post('/login', LoginController::class)->name('login');

Route::middleware('admin.jwt')->group(function () {
    Route::get('/', HomeController::class)->name('home');
    Route::get('/me', MeController::class)->name('me');
    Route::post('/logout', LogoutController::class)->name('logout');
});
