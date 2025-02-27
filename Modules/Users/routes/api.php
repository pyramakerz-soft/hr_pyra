<?php

use Illuminate\Support\Facades\Route;
use Modules\Users\Http\Controllers\DepartmentController;
use Modules\Users\Http\Controllers\PermissionController;
use Modules\Users\Http\Controllers\RoleController;
use Modules\Users\Http\Controllers\UsersController;
use Modules\Users\Http\Controllers\WorkTypeController;

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

Route::group(['middleware' => 'role:Hr'], function () {

    Route::group(['prefix' => 'users'], function () {

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



        Route::get('user_details', [UsersController::class, 'allUserDetails']); // Get all user details
        Route::get('user_details/{userDetail}', [UsersController::class, 'showUserDetails']); // Get a specific user detail



    });




    //WorkType Management
    Route::post('/work_types/{workType}', [WorkTypeController::class, 'update'])->name('work_types.update'); //HR role
    Route::apiResource('work_types', WorkTypeController::class)->except('update'); //HR role
    Route::get('employees_workTypes_percentage', [WorkTypeController::class, 'getEmployeesWorkTypesPercentage']); //HR role
    Route::get('users/workTypes', [WorkTypeController::class, 'getWorkTypeAssignedToUser'])->name('users.usersWorkTypes'); //HR role

    //Department Management
    Route::post('/departments/{department}', [DepartmentController::class, 'update'])->name('departments.update'); //HR role
    Route::apiResource('departments', DepartmentController::class)->except('update'); //HR role
    Route::get('department_employees', [DepartmentController::class, 'getDepartmentEmployees']); //HR role

    //Role and Permission Management
    Route::post('/roles/{role}', [RoleController::class, 'update'])->name('roles.update'); //HR role
    Route::post('/permissions/{permission}', [PermissionController::class, 'update'])->name('permissions.update'); //HR role
    Route::apiResource('roles', RoleController::class)->except('update'); //HR role
    Route::apiResource('permissions', PermissionController::class)->except('update'); //HR role



});
