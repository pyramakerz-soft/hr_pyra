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


    }
      /**
     * @OA\Get(
     *     path="/api/roles",
     *     summary="Get all roles",
     *     tags={"Roles"},
     *     @OA\Response(
     *         response=200,
     *         description="Roles retrieved successfully",
     *     ),
     *     @OA\Response(response=404, description="No Roles Found")
     * )
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
     * @OA\Post(
     *     path="/api/roles",
     *     summary="Create a new role",
     *     tags={"Roles"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "permissions"},
     *             @OA\Property(property="name", type="string", example="admin"),
     *             @OA\Property(property="permissions", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(response=201, description="Role created successfully"),
     *     @OA\Response(response=400, description="Failed to Store Role")
     * )
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
     * @OA\Get(
     *     path="/api/roles/{id}",
     *     summary="Get role details",
     *     tags={"Roles"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Role data retrieved successfully"),
     *     @OA\Response(response=404, description="Role not found")
     * )
     */
    public function show(Role $role)
    {

        $role = Role::with('permissions')->where('id', $role->id)->get();

        return $this->returnData('role', $role, 'Role Data');
    }

      /**
     * @OA\Put(
     *     path="/api/roles/{id}",
     *     summary="Update a role",
     *     tags={"Roles"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "permissions"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="permissions", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(response=200, description="Role updated successfully"),
     *     @OA\Response(response=400, description="Failed to update Role")
     * )
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
     * @OA\Delete(
     *     path="/api/roles/{id}",
     *     summary="Delete a role",
     *     tags={"Roles"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Role deleted successfully"),
     *     @OA\Response(response=404, description="Role not found")
     * )
     */
    public function destroy(Role $role)
    {

        $role->delete();
        return $this->returnData('role', $role, 'Role deleted Successfully');

    }
}
