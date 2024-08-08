<?php

use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\ClockController;
use App\Http\Controllers\DepartmentController;
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
    // Route::get('users', [UserController::class, 'getAllUsers']);
    // Route::post('create_user', [UserController::class, 'createUser'])->name('user.create');
    // Route::post('update_user/{user}', [UserController::class, 'updateUser'])->name('user.update');
    // Route::post('login', [UserController::class, 'login']);
    // Route::post('logout', [UserController::class, 'logout']);
    // Route::get('profile', [UserController::class, 'profile']);

    Route::post('/departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
    Route::apiResource('users', UserController::class)->except('update');

});
Route::group(['middleware' => 'auth:api'], function () {
    Route::post('/departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
    Route::apiResource('departments', DepartmentController::class)->except('update');
    Route::post('users/{user}/clock-in', [ClockController::class, 'clockIn'])->name('users.clock-in');
    Route::post('users/{user}/clock-out', [ClockController::class,'clockOut'])->name('users.clock-out');
});



