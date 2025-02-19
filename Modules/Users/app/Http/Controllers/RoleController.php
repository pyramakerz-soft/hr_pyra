<?php

namespace Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Api\AuthorizationService;
use App\Traits\ResponseTrait;
use Modules\Users\Http\Requests\Api\Role\StoreRoleRequest;
use Modules\Users\Http\Requests\Api\Role\UpdateRoleRequest;
use Modules\Users\Resources\RoleResource;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    protected $authorizationService;
    use ResponseTrait;
    public function __construct(AuthorizationService $authorizationService)
    {
        $this->authorizationService = $authorizationService;
        // $this->middleware("permission:role-list")->only(['index', 'show']);
        // $this->middleware("permission:role-create")->only(['store']);
        // $this->middleware("permission:role-edit")->only(['update']);
        // $this->middleware("permission:role-delete")->only(['destroy']);

    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $roles = Role::with('permissions')->paginate();
        if ($roles->isEmpty()) {
            return $this->returnError('No Roles Found');
        }
        return $this->returnData('roles', RoleResource::collection($roles), 'Roles Data');

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRoleRequest $request)
    {

        $role = Role::create(['name' => $request->name]);
        $role->syncPermissions($request->input('permission'));
        if (!$role) {
            return $this->returnError('Failed to Store Role');
        }
        return $this->returnData('role', new RoleResource($role), 'Role Stored Successfully');

    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role)
    {

        $role = Role::with('permissions')->where('id', $role->id)->get();

        return $this->returnData('role', $role, 'Role Data');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoleRequest $request, Role $role)
    {

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
