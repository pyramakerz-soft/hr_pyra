<?php

namespace Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Users\Http\Requests\CustomVacation\StoreCustomVacationRequest;
use Modules\Users\Http\Requests\CustomVacation\UpdateCustomVacationRequest;
use Modules\Users\Models\CustomVacation;

class CustomVacationController extends Controller
{
    use ResponseTrait;

    public function index(Request $request)
    {
        $query = CustomVacation::query()
            ->with([
                'departments:id,name',
                'subDepartments:id,name,department_id',
            ]);

        if ($search = $request->query('search')) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        if ($request->filled('from_date')) {
            $from = Carbon::parse($request->query('from_date'))->startOfDay();
            $query->whereDate('end_date', '>=', $from);
        }

        if ($request->filled('to_date')) {
            $to = Carbon::parse($request->query('to_date'))->endOfDay();
            $query->whereDate('start_date', '<=', $to);
        }

        if ($request->filled('department_id')) {
            $departmentId = (int) $request->query('department_id');
            $query->where(function ($builder) use ($departmentId) {
                $builder->whereHas('departments', function ($relation) use ($departmentId) {
                    $relation->where('departments.id', $departmentId);
                })->orWhereHas('subDepartments', function ($relation) use ($departmentId) {
                    $relation->where('sub_departments.department_id', $departmentId);
                });
            });
        }

        if ($request->filled('sub_department_id')) {
            $subDeptId = (int) $request->query('sub_department_id');
            $query->whereHas('subDepartments', function ($relation) use ($subDeptId) {
                $relation->where('sub_departments.id', $subDeptId);
            });
        }

        $perPage = (int) $request->query('per_page', 15);
        if ($perPage < 1) {
            $perPage = 15;
        }

        $vacations = $query->orderBy('start_date')
            ->paginate($perPage)
            ->appends($request->query());

        return $this->returnData('vacations', $vacations, 'Custom vacations list');
    }

    public function store(StoreCustomVacationRequest $request)
    {
        $data = $request->validated();

        $vacation = DB::transaction(function () use ($data) {
            $vacation = CustomVacation::create([
                'name' => $data['name'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'is_full_day' => $data['is_full_day'] ?? true,
                'description' => $data['description'] ?? null,
                'created_by' => Auth::id(),
            ]);

            if (array_key_exists('department_ids', $data) && empty($data['sub_department_ids'])) {
                $vacation->departments()->sync($data['department_ids'] ?? []);
            }

            if (array_key_exists('sub_department_ids', $data)) {
                $vacation->subDepartments()->sync($data['sub_department_ids'] ?? []);
            }

            return $vacation->load(['departments:id,name', 'subDepartments:id,name,department_id']);
        });

        return $this->returnData('vacation', $vacation, 'Custom vacation created successfully');
    }

    public function show(CustomVacation $customVacation)
    {
        $customVacation->load(['departments:id,name', 'subDepartments:id,name,department_id']);

        return $this->returnData('vacation', $customVacation, 'Custom vacation details');
    }

    public function update(UpdateCustomVacationRequest $request, CustomVacation $customVacation)
    {
        $data = $request->validated();

        $start = isset($data['start_date'])
            ? Carbon::parse($data['start_date'])
            : $customVacation->start_date;

        $end = isset($data['end_date'])
            ? Carbon::parse($data['end_date'])
            : $customVacation->end_date;

        if ($end->lt($start)) {
            return $this->returnError('The end date cannot be before the start date', 422);
        }

        DB::transaction(function () use ($data, $customVacation) {
            $customVacation->fill([
                'name' => $data['name'] ?? $customVacation->name,
                'start_date' => $data['start_date'] ?? $customVacation->start_date,
                'end_date' => $data['end_date'] ?? $customVacation->end_date,
                'is_full_day' => $data['is_full_day'] ?? $customVacation->is_full_day,
                'description' => $data['description'] ?? $customVacation->description,
            ])->save();

            if (array_key_exists('department_ids', $data) && empty($data['sub_department_ids'])) {
                $customVacation->departments()->sync($data['department_ids'] ?? []);
            }

            if (array_key_exists('sub_department_ids', $data)) {
                $customVacation->subDepartments()->sync($data['sub_department_ids'] ?? []);
            }
        });

        $customVacation->load(['departments:id,name', 'subDepartments:id,name,department_id']);

        return $this->returnData('vacation', $customVacation, 'Custom vacation updated successfully');
    }

    public function destroy(CustomVacation $customVacation)
    {
        $customVacation->delete();

        return $this->returnSuccessMessage('Custom vacation deleted successfully');
    }
}

