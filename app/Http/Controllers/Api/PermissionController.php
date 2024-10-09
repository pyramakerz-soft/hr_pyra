<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\PermissionResource;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    use ResponseTrait;
    public function __construct()
    {
        $this->middleware("permission:permission-list")->only(['index', 'show']);
        $this->middleware("permission:permission-create")->only(['store']);
        $this->middleware("permission:permission-edit")->only(['update']);
        $this->middleware("permission:permission-delete")->only(['destroy']);
    }
    public function index()
    {
        $permissions = Permission::all();
        if ($permissions->isEmpty()) {
            return $this->returnError("No Permissions Found");
        }
        return $this->returnData('permissions', PermissionResource::collection($permissions), "Permissions Data");

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required | string | unique:permissions,name',
        ]);
        $permission = Permission::create([
            'name' => $request->name,
        ]);
        return $this->returnData('permission', $permission, "Permission Stored Successfully");
    }

    /**
     * Display the specified resource.
     */
    public function show(Permission $permission)
    {
        return $this->returnData('permission', $permission, "Permission Data");

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Permission $permission)
    {
        $this->validate($request, [
            'name' => 'required|string',
        ]);
        $permission->update(['name' => $request->name]);
        return $this->returnData('permission', $permission, "Permission Updated Successfully");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Permission $permission)
    {
        $permission->delete();
        return $this->returnData('permission', $permission, "Permission deleted Successfully");

    }
}
