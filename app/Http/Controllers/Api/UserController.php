<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Requests\Api\UpdateUserRequest;
use App\Http\Resources\Api\UserDetailResource;
use App\Http\Resources\Api\UserResource;
use App\Models\Department;
use App\Models\User;
use App\Models\UserDetail;
use App\Services\Api\UserDetailService;
use App\Services\Api\UserService;
use App\Traits\HelperTrait;
use App\Traits\ResponseTrait;
use App\Traits\UserTrait;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    use ResponseTrait, HelperTrait, UserTrait;
    protected $userService;
    protected $userDetailService;

    public function __construct(UserService $userService, UserDetailService $userDetailService)
    {
        $this->userService = $userService;
        $this->userDetailService = $userDetailService;

        $this->middleware('auth:api')->except(['store']);
    }

    public function index()
    {
        $authUser = Auth::user();

        if (!$authUser->hasRole('Hr')) {
            return $this->returnError('You are not authorized to Update users', 403);
        }
        if (request()->has('search')) {
            $users = UserResource::collection(
                User::where('name', 'like', '%' . request()->get('search', '') . '%')
                    ->orWhere('code', 'like', '%' . request()->get('search', '') . '%')->get()
            );
            if ($users->isEmpty()) {
                return $this->returnError('No Users Found');
            }
            $data[] = [
                'users' => UserResource::collection($users),

            ];
        } else {
            $users = User::paginate(5);
            $data[] = [
                'users' => UserResource::collection($users),
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'next_page_url' => $users->nextPageUrl(),
                    'previous_page_url' => $users->previousPageUrl(),
                    'last_page' => $users->lastPage(),
                    'total' => $users->total(),
                ],

            ];
        }

        return $this->returnData("data", $data, "Users Data");
    }
    public function ManagerNames()
    {
        $managerData = DB::table('model_has_roles')
            ->where('role_id', 2)
            ->get();

        foreach ($managerData as $manager) {
            $user = User::find($manager->model_id);
            if ($user) {
                $data[] = [
                    'manager_id' => $user->id,
                    'manager_name' => $user->name,
                ];
            }
        }

        return $this->returnData('managerNames', $data, 'manager names');

    }

    public function store(RegisterRequest $request)
    {
        $data = [];
        $authUser = Auth::user();

        if (!$authUser->hasRole('Hr')) {
            return $this->returnError('You are not authorized to create users', 403);
        }
        $user = $this->userService->createUser($request->validated());
        if (!$user) {
            return $this->returnError('Failed to Store User');
        }

        $userDetail = $this->userDetailService->createUserDetail($user, $request->validated());
        if (!$userDetail) {
            return $this->returnError('Failed to Store User Detail');
        }
        $this->assignRoles($user, $request['roles'] ?? []);
        $this->assignLocations($user, $request['location_id'] ?? []);
        $this->assignWorkTypes($user, $request['work_type_id'] ?? []);

        $data = [
            'user' => $user,
            'user_detail' => $userDetail,
        ];

        return $this->returnData("data", $data, "User Created");
    }

    public function show(User $user)
    {
        $authUser = Auth::user();

        if (!$authUser->hasRole('Hr')) {
            return $this->returnError('You are not authorized to Update users', 403);
        }
        $userDetail = UserDetail::where('user_id', $user->id)->first();
        return $this->returnData("User", new UserDetailResource($userDetail), "User Data");
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $data = [];
        $authUser = Auth::user();

        if (!$authUser->hasRole('Hr')) {
            return $this->returnError('You are not authorized to update users', 403);
        }
        $department_id = $request->has('department_id') ? $request->department_id : $user->department_id;
        $department = Department::find($department_id);
        if (!$department) {
            return $this->returnError('Invalid department selected', Response::HTTP_BAD_REQUEST);
        }

        $updatedUser = $this->userService->updateUser($user, $request->validated());
        if (!$updatedUser) {
            return $this->returnError('Failed to Update User');
        }

        $userDetail = UserDetail::where('user_id', $user->id)->first();
        if (!$userDetail) {
            return $this->returnError('No User Detail Found for this User');
        }

        $updatedUserDetail = $this->userDetailService->updateUserDetail($userDetail, $request->validated());
        if (!$updatedUserDetail) {
            return $this->returnError('Failed to Update User Detail');
        }
        // Assign roles, locations, and work types
        if ($request->has('roles')) {
            $this->assignRoles($user, $request->input('roles'));
        }

        if ($request->has('location_id')) {
            $this->assignLocations($user, $request->input('location_id'));
        }

        if ($request->has('work_type_id')) {
            $this->assignWorkTypes($user, $request->input('work_type_id'));
        }

        $data = [
            'user' => $updatedUser,
            'user_detail' => $updatedUserDetail,
        ];

        return $this->returnData("data", $data, "User Updated");
        // Update Assigning Roles to User
        // $newRoles = $request->input('roles') ?? $user->getRoleNames()->toArray();
        // $user->syncRoles($newRoles);

        // // Update Assigning Locations to User
        // $newLocations = $request->input('location_id') ?? $user->user_locations()->pluck('locations.id')->toArray();
        // $user->user_locations()->sync($newLocations);

        // // Update Assigning WorkTypes to User
        // $newWorkTypes = $request->input('work_type_id') ?? $user->work_types()->pluck('work_types.id')->toArray();
        // $user->work_types()->sync($newWorkTypes);

        // return $this->returnData("data", $finalData, "User Updated");
    }

    public function destroy(User $user)
    {
        $authUser = Auth::user();

        if (!$authUser->hasRole('Hr')) {
            return $this->returnError('You are not authorized to delete users', 403);
        }

        $user->delete();
        return $this->returnData("user", $user, "user deleted");
    }

    public function getAllUsersNames()
    {
        $usersByName = User::pluck('name');
        return $this->returnData("usersNames", $usersByName, "UsersName");
    }
    public function updatePassword(Request $request, User $user)
    {

        $request->validate([
            'password' => ['required', 'min:6'],
        ]);
        $user->update([
            'password' => bcrypt($request->password) ?? $user->password,
        ]);
        return $this->returnSuccessMessage("Password Updated Successfully");
    }

}
