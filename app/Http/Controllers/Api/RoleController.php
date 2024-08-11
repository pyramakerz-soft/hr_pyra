<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    use ResponseTrait;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $roles = Role::all();
        if ($roles->isEmpty()) {
            return $this->returnError('No Roles Found');
        }
        return $this->returnData('roles', $roles, 'Roles Data');

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // dd($request->toArray());
        $this->validate($request, [
            'name' => 'required|unique:roles,name',
            'permission' => 'required',
        ]);
        $role = Role::create(['name' => $request->name]);
        $role->syncPermissions($request->input('permission'));
        if (!$role) {
            return $this->returnError('Failed to Store Role');
        }
        return $this->returnData('role', $role, 'Role Stored Successfully');

    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role)
    {
        $rolePermissions = Permission::join("role_has_permissions", "role_has_permissions.permission_id", "=", "permissions.id")
            ->where("role_has_permissions.role_id", $role->id)
            ->get();

        return $this->returnData('rolePermissions', $rolePermissions, 'Role Permissions Data');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Role $role)
    {

        $this->validate($request, [
            'name' => 'required',
            'permission' => 'required',
        ]);
        $role->update(['name' => $request->name]);
        $role->syncPermissions($request->input('permission'));
        if (!$role) {
            return $this->returnError('Failed to update Role');
        }
        return $this->returnData('role', $role, 'Role updated Successfully');

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        $role->delete();
        return $this->returnData('role', $role, 'Role deleted Successfully');

    }
}
