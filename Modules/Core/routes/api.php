<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Core\App\Http\Controllers\CustomerController;

/*
    |--------------------------------------------------------------------------
    | API Routes
    |--------------------------------------------------------------------------
    |
    | Here is where you can register API routes for your application. These
    | routes are loaded by the RouteServiceProvider within a group which
    | is assigned the "api" middleware group. Enjoy building your API!
    |
*/


Route::prefix('/customer')->group(function() {
    Route::get('/select-search', [CustomerController::class,'selectSearch'])->name('customer_autosearch');
});
Route::apiResource('customer', 'CustomerController')->middleware('api.header.authentication');

