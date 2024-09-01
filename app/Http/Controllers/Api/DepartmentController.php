<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Http\Resources\Api\DepartmentResource;
use App\Models\Department;
use App\Traits\ResponseTrait;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class DepartmentController extends Controller
{
    use ResponseTrait;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $authUser = Auth::user();
        if (!$authUser->hasRole('Hr')) {
            return $this->returnError('You are not authorized to view departments', Response::HTTP_FORBIDDEN);
        }
        $departments = Department::with('manager')->get();
        if ($departments->isEmpty()) {
            return $this->returnError('No departments Found');
        }
        $data['departments'] = DepartmentResource::collection($departments);
        return $this->returnData("data", $data, "departments Data");

    }

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
        return $this->returnData("department", new DepartmentResource($department), "department Data");
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
