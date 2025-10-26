<?php

use Illuminate\Support\Facades\Route;
use Modules\Clocks\Http\Controllers\ClockController;
use Modules\Clocks\Http\Controllers\ClocksController;
use Modules\Clocks\Http\Controllers\DeductionPlanController;
use Modules\Clocks\Http\Controllers\DeductionRuleTemplateController;
use Modules\Clocks\Http\Controllers\ServiceActionController;

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

Route::group(['middleware' => ['auth:api', 'role:Hr|Team leader']], function () {


    Route::prefix('deduction-plans')->group(function () {
        Route::get('department/{department}', [DeductionPlanController::class, 'showDepartment']);
        Route::post('department/{department}', [DeductionPlanController::class, 'upsertDepartment']);
        Route::get('sub-department/{subDepartment}', [DeductionPlanController::class, 'showSubDepartment']);
        Route::post('sub-department/{subDepartment}', [DeductionPlanController::class, 'upsertSubDepartment']);
        Route::get('user/{user}', [DeductionPlanController::class, 'showUser']);
        Route::post('user/{user}', [DeductionPlanController::class, 'upsertUser']);
    });

    Route::prefix('deduction-rule-templates')->group(function () {
        Route::get('/', [DeductionRuleTemplateController::class, 'index']);
        Route::post('/', [DeductionRuleTemplateController::class, 'store']);
        Route::get('{template}', [DeductionRuleTemplateController::class, 'show']);
        Route::put('{template}', [DeductionRuleTemplateController::class, 'update']);
        Route::delete('{template}', [DeductionRuleTemplateController::class, 'destroy']);
    });

    //Clocks Management

    Route::get('/all_clocks', [ClockController::class, 'allClocks'])->name('clocks.allClocks'); //HR role
    Route::get('/clocks/user/{user}', [ClockController::class, 'getUserClocksById'])->name('clocks.userById'); //HR role

    Route::get('/clock_by_id/{clock}', [ClockController::class, 'getClockById']); //HR role
    Route::post('/update_clock/user/{user}/clock/{clock}', [ClockController::class, 'updateUserClock'])->name('clocks.updateUserClock'); //HR role
    Route::post('/clock_in/user/{user}', [ClockController::class, 'hrClocKIn'])->name('hr.ClocKIn'); //HR role
    Route::get('/get_clock_issues', [ClockController::class, 'getClockIssues']);
    Route::get('/get_count_issues', [ClockController::class, 'getCountIssues']);
    Route::post('/update_clock_issue/{clock}', [ClockController::class, 'updateClockIssues']);


    Route::post('/update_clock_issue/{clock}', [ClockController::class, 'updateClockIssues']);

    Route::get('/users_clocks_Ins', [ClockController::class, 'getUsersClockInStatus']);

    Route::get('/users_clocks_Outs', [ClockController::class, 'getUsersClockOutStatus']);
    Route::get('/getAbsentUser', [ClockController::class, 'getAbsentUser'])->name('clocks.getAbsentUser'); //HR role

    Route::prefix('service-actions')->group(function () {
        Route::get('/', [ServiceActionController::class, 'index']);
        Route::get('/available', [ServiceActionController::class, 'available']);
        Route::post('/', [ServiceActionController::class, 'store']);
        Route::post('/revert', [ServiceActionController::class, 'revertLast']);
    });



});

Route::group(['middleware' => 'auth:api'], function () {
    Route::post('/clock_in', [ClockController::class, 'clockIn'])->name('clocks.clockIn');
    Route::post('/clock_out', [ClockController::class, 'clockOut'])->name('clocks.clockOut');
    Route::get('/user_clocks', [ClockController::class, 'showUserClocks'])->name('clocks.UserClocks');
    // Route::get('/test_clock_event', function () {
    //     event(new CheckClockOutsEvent());
    // });

});
