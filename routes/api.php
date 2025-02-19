<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClockController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserDetailController;
use App\Http\Controllers\Api\WorkTypeController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['auth:api', 'role:Hr']], function () {



    //Clocks Management

    Route::get('/all_clocks', [ClockController::class, 'allClocks'])->name('clocks.allClocks'); //HR role
    Route::get('/clocks/user/{user}', [ClockController::class, 'getUserClocksById'])->name('clocks.userById'); //HR role

    Route::get('/clock_by_id/{clock}', [ClockController::class, 'getClockById']); //HR role
    Route::post('/update_clock/user/{user}/clock/{clock}', [ClockController::class, 'updateUserClock'])->name('clocks.updateUserClock'); //HR role
    Route::post('/clock_in/user/{user}', [ClockController::class, 'hrClocKIn'])->name('hr.ClocKIn'); //HR role
    Route::get('/get_clock_issues', [ClockController::class, 'getClockIssues']);
    Route::get('/get_count_issues', [ClockController::class, 'getCountIssues']);
    Route::post('/update_clock_issue/{clock}', [ClockController::class, 'updateClockIssues']);



});

Route::group(['middleware' => 'auth:api'], function () {
    Route::post('/clock_in', [ClockController::class, 'clockIn'])->name('clocks.clockIn');
    Route::post('/clock_out', [ClockController::class, 'clockOut'])->name('clocks.clockOut');
    Route::get('/user_clocks', [ClockController::class, 'showUserClocks'])->name('clocks.UserClocks');
    // Route::get('/test_clock_event', function () {
    //     event(new CheckClockOutsEvent());
    // });

});

/*
//User_Holidays and User_Vacations ----Phase (2)

Route::post('/user_holidays/{user_holiday}', [UserHolidayController::class, 'update'])->name('user_holidays.update');
Route::post('/user_vacations/{user_vacation}', [UserVacationController::class, 'update'])->name('user_vacations.update');
Route::apiResource('user_holidays', UserHolidayController::class)->except('update');
Route::apiResource('user_vacations', UserVacationController::class)->except('update');

 */
