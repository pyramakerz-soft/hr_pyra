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
    Route::get('/user_by_token', [UserController::class, 'profile'])->name('user.profile');
    Route::get('/users_by_name', [UserController::class, 'getAllUsersNames'])->name('user.names');
    Route::post('/update_user/{user}', [UserController::class, 'update'])->name('user.update');
    Route::get('/getAllUsers', [UserController::class, 'index'])->name('users.all');
    Route::delete('/delete_user/{user}', [UserController::class, 'destroy'])->name('user.delete');
    Route::post('/create_user', [UserController::class, 'store'])->name('user.store');
    Route::post('login', [UserController::class, 'login'])->name('user.login');
    Route::post('logout', [UserController::class, 'logout'])->name('user.logout');
    Route::post('assign_role/{user}', [UserController::class, 'AssignRole'])->name('user.roles');
    Route::get('/get_user_by_id/{user}', [UserController::class, 'show'])->name('user.show');
    Route::post('update_password/{user}', [UserController::class, 'updatePassword'])->name('user.updatePassword');

});
Route::group(['middleware' => 'auth:api'], function () {

    Route::post('/upload_image', [UserController::class, 'uploadImage'])->name('users.image');
    Route::get('manager_names', [UserController::class, 'ManagerNames']);
    Route::post('/departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
    Route::post('/user_details/{user_detail}', [UserDetailController::class, 'update'])->name('user_details.update');
    Route::post('/user_holidays/{user_holiday}', [UserHolidayController::class, 'update'])->name('user_holidays.update');
    Route::post('/user_vacations/{user_vacation}', [UserVacationController::class, 'update'])->name('user_vacations.update');
    Route::post('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    Route::post('/permissions/{permission}', [PermissionController::class, 'update'])->name('permissions.update');
    Route::post('/locations/{location}', [LocationController::class, 'update'])->name('locations.update');
    Route::post('/work_types/{workType}', [WorkTypeController::class, 'update'])->name('work_types.update');

    Route::get('/all_clocks', [ClockController::class, 'allClocks'])->name('clocks.usersClocks');
    Route::get('/clocks/user/{user}', [ClockController::class, 'getUserClocksById'])->name('clocks.userById');
    Route::post('/clock_in', [ClockController::class, 'clockIn'])->name('clocks.clockIn');
    Route::post('/clock_out', [ClockController::class, 'clockOut'])->name('clocks.clockOut');
    Route::get('/user_clocks', [ClockController::class, 'showUserClocks'])->name('clocks.UserClocks');
    Route::post('/update_clock/user/{user}/clock/{clock}', [ClockController::class, 'updateUserClock'])->name('clocks.updateUserClock');
    Route::get('location_names', [LocationController::class, 'locationNames']);

    Route::apiResource('roles', RoleController::class)->except('update');
    Route::apiResource('permissions', PermissionController::class)->except('update');
    Route::apiResource('locations', LocationController::class)->except('update');
    Route::apiResource('work_types', WorkTypeController::class)->except('update');
    Route::apiResource('departments', DepartmentController::class)->except('update');
    Route::apiResource('user_details', UserDetailController::class)->except('update');
    Route::apiResource('user_holidays', UserHolidayController::class)->except('update');
    Route::apiResource('user_vacations', UserVacationController::class)->except('update');

    Route::post('users/{user}/locations', [HrController::class, 'assignLocationToUser'])->name('users.assignLocation');
    Route::get('users/{user}/locations', [HrController::class, 'getLocationAssignedToUser'])->name('users.userLocation');
    Route::post('/import-users-from-excel', [UserController::class, 'importUsersFromExcel']);

    Route::post('users/{user}/workTypes', [HrController::class, 'assignWorkTypeToUser'])->name('users.assignWorkType');
    Route::get('users/workTypes', [HrController::class, 'getWorkTypeAssignedToUser'])->name('users.usersWorkTypes');

    Route::post('clock_in/user/{user}', [HrController::class, 'hrClocKIn'])->name('hr.ClocKIn');

    Route::get('employees_workTypes_percentage', [HrController::class, 'getEmployeesWorkTypesPercentage']);
    Route::get('department_employees', [HrController::class, 'getDepartmentEmployees']);
    Route::get('employees_per_month', [HrController::class, 'employeesPerMonth']);

});
