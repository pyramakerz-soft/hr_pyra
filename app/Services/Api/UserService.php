<?php

namespace App\Services\Api;

use App\Models\User;
use App\Traits\ResponseTrait;
use App\Traits\UserTrait;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

class UserService
{
    use ResponseTrait, UserTrait;

    public function createUser($data)
    {
        // dd($data);
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

}
