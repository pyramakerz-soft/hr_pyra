<?php

use Illuminate\Support\Facades\Route;
use Modules\Location\Http\Controllers\LocationController;

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

Route::group(['middleware' => ['auth:api', 'role:Hr']], function () {

    //Location Management
    Route::post('/locations/{location}', [LocationController::class, 'update'])->name('locations.update'); //HR role
    Route::get('location_names', [LocationController::class, 'locationNames']); //HR role
    Route::apiResource('locations', LocationController::class)->except('update'); //HR role
    Route::get('users/{user}/locations', [LocationController::class, 'getLocationAssignedToUser'])->name('users.userLocation'); //HR role

});