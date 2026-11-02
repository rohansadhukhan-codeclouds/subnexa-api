<?php

use App\Http\Controllers\Api\StatusController;
use Illuminate\Support\Facades\Route;

Route::get('/status', StatusController::class);

Route::prefix('admin')->name('admin.')->group(__DIR__.'/api/admin.php');
Route::prefix('storefront')->name('storefront.')->group(__DIR__.'/api/storefront.php');
