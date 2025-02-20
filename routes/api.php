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



/*
//User_Holidays and User_Vacations ----Phase (2)

Route::post('/user_holidays/{user_holiday}', [UserHolidayController::class, 'update'])->name('user_holidays.update');
Route::post('/user_vacations/{user_vacation}', [UserVacationController::class, 'update'])->name('user_vacations.update');
Route::apiResource('user_holidays', UserHolidayController::class)->except('update');
Route::apiResource('user_vacations', UserVacationController::class)->except('update');

 */
