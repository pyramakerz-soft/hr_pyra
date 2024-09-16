<?php

namespace App\Services\Api\User;

use App\Http\Resources\Api\UserResource;
use App\Models\Department;
use App\Models\User;
use App\Traits\ResponseTrait;
use App\Traits\UserTrait;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserService
{
    use ResponseTrait, UserTrait;

    public function createUser($data)
    {
        // Validate department inside createUser
        $department = Department::find($data['department_id']);
        if (!$department) {
            return $this->returnError('Invalid department selected', Response::HTTP_BAD_REQUEST);
        }
        $code = $this->generateUniqueCode($data['department_id']);
        if (!$code) {
            return $this->returnError('Invalid department selected', Response::HTTP_BAD_REQUEST);
        }

        $imageUrl = $this->uploadImage($data['image']); // Update to match your parameter passing
        // dd($imageUrl);
        if (!$imageUrl) {
            return $this->returnError('Failed to upload image', Response::HTTP_BAD_REQUEST);
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone' => $data['phone'],
            'contact_phone' => $data['contact_phone'],
            'national_id' => $data['national_id'],
            'code' => $code,
            'gender' => $data['gender'],
            'department_id' => (int) $data['department_id'],
            'image' => $imageUrl,
            'serial_number' => null,
        ]);

        if (!$user) {
            return $this->returnError('Failed to create user', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $user;
    }

    public function updateUser($user, $data)
    {
        // Validate department inside updateUser
        $department = Department::find($data['department_id'] ?? $user->department_id);
        if (!$department) {
            return $this->returnError('Invalid department selected', Response::HTTP_BAD_REQUEST);
        }

        $code = $this->generateUniqueCode($data['department_id']);
        $code = $code ?? $user->code;

        // Check if an image is provided in the request
        if (isset($data['image']) && $data['image']->isValid()) {
            $imageUrl = $this->uploadImage($data['image']);
        } else {
            $imageUrl = $user->image;
        }

        // Update user information
        $user->update([
            'name' => $data['name'] ?? $user->name,
            'email' => $data['email'] ?? $user->email,
            'phone' => $data['phone'] ?? $user->phone,
            'contact_phone' => $data['contact_phone'] ?? $user->contact_phone,
            'national_id' => $data['national_id'] ?? $user->national_id,
            'code' => $code,
            'gender' => $data['gender'] ?? $user->gender,
            'department_id' => (int) $data['department_id'],
            'image' => $imageUrl,
        ]);

        return $user;
    }
    public function getAllUsers($search = null)
    {
        if ($search) {
            $users = $this->searchUsersByNameOrCode($search);
            if ($users->isEmpty()) {
                return null; // Handle no users found case in controller
            }
            return [
                'users' => UserResource::collection($users),
            ];
        } else {
            $users = User::paginate(5);
            return [
                'users' => UserResource::collection($users),
                'pagination' => $this->formatPagination($users),
            ];
        }
    }
    public function getManagerNames()
    {

        $data = [];
        $role = Role::where('name', 'Manager')->first();
        if (!$role) {
            return $this->returnError('Manager role not found', 404);
        }
        $managers = User::Role('manager')->get(['id', 'name']);
        $data = $managers->map(function ($manager) {
            return [
                'manager_id' => $manager->id,
                'manager_name' => $manager->name,
            ];
        });
        return $data;
    }
}
