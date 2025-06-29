<?php

use Illuminate\Support\Facades\Route;
use Modules\Users\Http\Controllers\DepartmentController;
use Modules\Users\Http\Controllers\ExcuseController;
use Modules\Users\Http\Controllers\OverTimeController;
use Modules\Users\Http\Controllers\PermissionController;
use Modules\Users\Http\Controllers\RoleController;
use Modules\Users\Http\Controllers\TimezoneController;
use Modules\Users\Http\Controllers\UsersController;
use Modules\Users\Http\Controllers\UserVacationController;
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


Route::group(['prefix' => 'excuse'], function () {



    Route::post('/add_user_excuse', [ExcuseController::class, 'addUserExcuse'])->name('add_user_excuse'); //HR role
    Route::get('/show_user_excuses', [ExcuseController::class, 'showUserExcuses']);

    Route::group(['middleware' => 'role:Manager|Team leader|Hr|Admin'], function () {

        Route::post('/change_excuse_status/{excuse}', [ExcuseController::class, 'changeExcuseStatus']);
        Route::get('/get_excuses_of_manager_employees', [ExcuseController::class, 'getExcusesOfManagerEmployees']);
    });
});

Route::group(['prefix' => 'overtime'], function () {

    Route::post('/start_user_overtime', [OverTimeController::class, 'addStartUserOvertime'])->name('start_user_overtime');
    Route::post('/end_user_overtime', [OverTimeController::class, 'addEndUserOvertime'])->name('end_user_overtime');
    Route::get('/show_user_overtime', [OverTimeController::class, 'showUserOvertime']);

    Route::group(['middleware' => 'role:Manager|Team leader|Hr|Admin'], function () {

        // Route to change overtime status
        Route::post('/change_overtime_status/{overtime}', [OverTimeController::class, 'changeOvertimeStatus']);
        Route::get('/get_overtime_of_manager_employees', [OverTimeController::class, 'getOvertimeOfManagerEmployees']);
    });
});


Route::group(['prefix' => 'vacation'], function () {

    Route::post('/add_user_vacation', [UserVacationController::class, 'addUserVacation'])->name('add_user_vacation');
    Route::get('/show_user_vacations', [UserVacationController::class, 'showUserVacations']);

    Route::group(['middleware' => 'role:Manager|Team leader|Hr|Admin'], function () {

        Route::post('/change_vacation_status/{vacation}', [UserVacationController::class, 'changeVacationStatus']);
        Route::get('/get_vacations_of_manager_employees', [UserVacationController::class, 'getVacationsOfManagerEmployees']);
    });
});



Route::group(['middleware' => 'role:Hr|Admin|Team leader'], function () {



Route::get('timezones', [TimezoneController::class, 'index']);
Route::get('timezones/{id}', [TimezoneController::class, 'show']);
Route::post('timezones', [TimezoneController::class, 'store']);
Route::put('timezones/{id}', [TimezoneController::class, 'update']);
Route::delete('timezones/{id}', [TimezoneController::class, 'destroy']);


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
      
        Route::get('/teamlead_names', [UsersController::class, 'teamleadNames']); //HR role

      
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


    // sub Depratment
    Route::get('/departments/{departmentId}/sub-departments', [DepartmentController::class, 'getSubDepartment'])->name('departments.getSubDepartment'); //HR role
   
   
        Route::get('/departments/{departmentId}/sub-departments/{subDepartmentId}', [DepartmentController::class, 'getSubDepartmentById'])->name('departments.getSubDepartmentById'); //HR role

    Route::post('/departments/{departmentId}/sub-departments', [DepartmentController::class, 'storeSubDepartment'])->name('departments.storeSubDepartment'); //HR role
    Route::post('/departments/{departmentId}/sub-departments/{subDepartmentId}', [DepartmentController::class, 'updateSubDepartment'])->name('departments.updateSubDepartment'); //HR role

    Route::delete('/departments/{departmentId}/sub-departments/{subDepartmentId}', [DepartmentController::class, 'deleteSubDepartment'])->name('departments.deleteSubDepartment'); //HR role




    //Role and Permission Management
    Route::post('/roles/{role}', [RoleController::class, 'update'])->name('roles.update'); //HR role
    Route::post('/permissions/{permission}', [PermissionController::class, 'update'])->name('permissions.update'); //HR role
    Route::apiResource('roles', RoleController::class)->except('update'); //HR role
    Route::apiResource('permissions', PermissionController::class)->except('update'); //HR role





});
