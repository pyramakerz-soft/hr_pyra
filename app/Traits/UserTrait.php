<?php

namespace App\Traits;

use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Str;

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

    public function uploadImage($image)
    {
        if ($image->isValid()) {
            $path = public_path('assets/images/Users');
            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            }

            $newImageName = uniqid() . "-employee." . $image->extension();
            $image->move($path, $newImageName);

            return asset('assets/images/Users/' . $newImageName);
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
                $user->user_locations()->sync($locationIds);
            }
        }
    }

    public function assignWorkTypes($user, $workTypeIds)
    {
        foreach ($workTypeIds as $workTypeId) {
            if (!$user->work_types()->wherePivot('work_type_id', $workTypeId)->exists()) {
                $user->work_types()->sync($workTypeIds);
            }
        }
    }
    public function searchUsersByNameOrCode($search)
    {
        return User::where('name', 'like', '%' . $search . '%')
            ->orWhere('code', 'like', '%' . $search . '%')
            ->get();
    }

    public function formatPagination($users)
    {
        return [
            'current_page' => $users->currentPage(),
            'next_page_url' => $users->nextPageUrl(),
            'previous_page_url' => $users->previousPageUrl(),
            'last_page' => $users->lastPage(),
            'total' => $users->total(),
        ];
    }

}
