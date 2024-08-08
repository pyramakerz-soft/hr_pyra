<?php

use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserDetailController;
use App\Http\Controllers\Api\UserHolidayController;
use App\Http\Controllers\Api\UserVacationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
 */

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::group(['prefix' => 'auth'], function () {
    Route::get('/profile', [UserController::class, 'profile'])->name('users.profile');
    Route::post('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::post('login', [UserController::class, 'login'])->name('users.login');
    Route::post('logout', [UserController::class, 'logout'])->name('users.logout');
    Route::apiResource('users', UserController::class)->except('update');
});
Route::group(['middleware' => 'auth:api'], function () {

    Route::post('/departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
    Route::post('/user_details/{user_detail}', [UserDetailController::class, 'update'])->name('user_details.update');
    Route::post('/user_holidays/{user_holiday}', [UserHolidayController::class, 'update'])->name('user_holidays.update');
    Route::post('/user_vacations/{user_vacation}', [UserVacationController::class, 'update'])->name('user_vacations.update');

    Route::apiResource('departments', DepartmentController::class)->except('update');
    Route::apiResource('user_details', UserDetailController::class)->except('update');
    Route::apiResource('user_holidays', UserHolidayController::class)->except('update');
    Route::apiResource('user_vacations', UserVacationController::class)->except('update');

});