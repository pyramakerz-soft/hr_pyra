<?php

namespace App\Traits;

use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

trait UserTrait
{
    public function generateUniqueCode($departmentId)
    {
        $department = Department::find((int) $departmentId);
        if (!$department) {
            return null;
        }

        do {
            $departmentPrefix = substr(Str::slug($department->name), 0, 4);
            $randomDigits = mt_rand(1000, 9999);
            $code = strtoupper($departmentPrefix) . '-' . $randomDigits;
        } while (User::where('code', $code)->exists());

        return $code;
    }

    public function uploadImage(Request $request, $inputName = 'image', $directory = 'assets/images/Users')
    {
        if ($request->hasFile($inputName)) {
            $path = public_path($directory);
            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            }

            $newImageName = uniqid() . "-employee." . $request->file($inputName)->extension();
            $request->file($inputName)->move($path, $newImageName);

            return asset($directory . '/' . $newImageName);
        }

        return false;
    }

    public function assignRoles($user, $roles)
    {
        $user->syncRoles($roles);
    }

    public function assignLocations($user, $locationIds)
    {
        foreach ($locationIds as $locationId) {
            if (!$user->user_locations()->wherePivot('location_id', $locationId)->exists()) {
                $user->user_locations()->attach($locationId);
            }
        }
    }

    public function assignWorkTypes($user, $workTypeIds)
    {
        foreach ($workTypeIds as $workTypeId) {
            if (!$user->work_types()->wherePivot('work_type_id', $workTypeId)->exists()) {
                $user->work_types()->attach($workTypeId);
            }
        }
    }
}