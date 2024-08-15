<?php

use App\Http\Controllers\Api\ClockController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\HrController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserDetailController;
use App\Http\Controllers\Api\UserHolidayController;
use App\Http\Controllers\Api\UserVacationController;
use App\Http\Controllers\Api\WorkTypeController;
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
    Route::post('assign_role/{user}', [UserController::class, 'AssignRole'])->name('users.role');

    Route::apiResource('users', UserController::class)->except('update');

});
Route::group(['middleware' => 'auth:api'], function () {

    Route::post('/departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
    Route::post('/user_details/{user_detail}', [UserDetailController::class, 'update'])->name('user_details.update');
    Route::post('/user_holidays/{user_holiday}', [UserHolidayController::class, 'update'])->name('user_holidays.update');
    Route::post('/user_vacations/{user_vacation}', [UserVacationController::class, 'update'])->name('user_vacations.update');
    Route::post('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    Route::post('/permissions/{permission}', [PermissionController::class, 'update'])->name('permissions.update');
    Route::post('/locations/{location}', [LocationController::class, 'update'])->name('locations.update');
    Route::post('/work_types/{workType}', [WorkTypeController::class, 'update'])->name('work_types.update');

    Route::post('/clock_in', [ClockController::class, 'clockIn'])->name('clocks.clockIn');
    Route::post('/clock_out/{clock}', [ClockController::class, 'clockOut'])->name('clocks.clockOut');

    // Route::post('users/{user}/clock-in', [ClockController::class, 'clockIn'])->name('users.clock-in');
    // Route::post('users/{user}/clock-out', [ClockController::class, 'clockOut'])->name('users.clock-out');
    Route::apiResource('roles', RoleController::class)->except('update');
    Route::apiResource('permissions', PermissionController::class)->except('update');
    Route::apiResource('locations', LocationController::class)->except('update');
    Route::apiResource('clocks', ClockController::class)->except(['store', 'update']);
    Route::apiResource('work_types', WorkTypeController::class)->except('update');

    Route::apiResource('departments', DepartmentController::class)->except('update');
    Route::apiResource('user_details', UserDetailController::class)->except('update');
    Route::apiResource('user_holidays', UserHolidayController::class)->except('update');
    Route::apiResource('user_vacations', UserVacationController::class)->except('update');
    // Route::group('')
    Route::post('users/{user}/locations', [HrController::class, 'assignLocationToUser'])->name('users.assignLocation');
    Route::get('users/locations', [HrController::class, 'getLocationAssignedToUser'])->name('users.userLocation');

    Route::post('users/{user}/workTypes', [HrController::class, 'assignWorkTypeToUser'])->name('users.assignWorkType');
    Route::get('users/workTypes', [HrController::class, 'getWorkTypeAssignedToUser'])->name('users.usersWorkTypes');

});
