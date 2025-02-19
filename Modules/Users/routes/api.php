<?php

use Illuminate\Support\Facades\Route;
use Modules\Users\Http\Controllers\UsersController;

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

Route::group(['prefix' => 'users'], function () {
  
    Route::group(['middleware' => 'role:Hr'], function () {
        Route::get('/users_by_name', [UsersController::class, 'getAllUsersNames'])->name('user.names');
        Route::post('/update_user/{user}', [UsersController::class, 'update'])->name('user.update');
        Route::get('/getAllUsers', [UsersController::class, 'index'])->name('users.all');
        Route::delete('/delete_user/{user}', [UsersController::class, 'destroy'])->name('user.delete');
        Route::post('/create_user', [UsersController::class, 'store'])->withoutMiddleware('auth:api')->name('user.store'); // Excluding middleware for this route
        Route::get('/get_user_by_id/{user}', [UsersController::class, 'show'])->name('user.show');
        Route::post('/update_password/{user}', [UsersController::class, 'updatePassword'])->name('user.updatePassword');

        Route::post('/import-users-from-excel', [UsersController::class, 'importUsersFromExcel']);

        //User Management
        Route::get('/manager_names', [UsersController::class, 'ManagerNames']); //HR role
        Route::get('/employees_per_month', [UsersController::class, 'employeesPerMonth']); //HR role

    });

});