<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Models\Department;
use App\Traits\ResponseTrait;

class DepartmentController extends Controller
{
    use ResponseTrait;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $departments = Department::all();
        if ($departments->isEmpty()) {
            return $this->returnError('No departments Found');
        }
        $data['departments'] = $departments;
        return $this->returnData("data", $data, "departments Data");

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDepartmentRequest $request)
    {
        $department = new Department();
        $department->name = $request->name;
        $department->manager_id = $request->manager_id;

        if ($department->save()) {
            return $this->returnData("department", $department, "department stored successfully");
        }

    }

    /**
     * Display the specified resource.
     */
    public function show(Department $department)
    {
        return $this->returnData("department", $department, "department Data");
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDepartmentRequest $request, Department $department)
    {
        $department->name = $request->name;
        $department->manager_id = $request->manager_id;
        if ($department->save()) {
            return $this->returnData("department", $department, "department updated successfully");
        }

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Department $department)
    {
        if ($department->delete()) {
            return $this->returnData("department", $department, "department deleted successfully");
        }

    }
}
