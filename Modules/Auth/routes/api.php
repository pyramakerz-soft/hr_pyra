<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\AuthController;

/*
 *--------------------------------------------------------------------------
 * API Routes
 *--------------------------------------------------------------------------
 *
 * Here is where you can register API routes for your application. These
 * routes are loaded by the RouteServiceProvider within a group which
 * is assigned the "api" middleware group. Enjoy building your API!
 *
*/
Route::group(['prefix' => 'auth'], function () {
    Route::post('login', [AuthController::class, 'login'])->name('auth.login');
    Route::post('logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::get('/user_by_token', [AuthController::class, 'profile'])->name('auth.profile');
    Route::post('remove_serial_number/{user}', [AuthController::class, 'removeSerialNumber'])->name('user.removeSerialNumber');
    Route::get('check_serial_number/{user}', [AuthController::class, 'checkSerialNumber'])->name('user.checkSerialNumber');

});