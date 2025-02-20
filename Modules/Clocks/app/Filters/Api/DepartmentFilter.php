<?php
namespace Modules\Clocks\Filters\Api;

use Illuminate\Http\Request;

class DepartmentFilter implements ClockFilter
{
    public function apply($query, Request $request)
    {
        if ($request->has('department')) {
            $departmentName = $request->get('department');

            $query->join('users', 'users.id', '=', 'clock_in_outs.user_id')
                ->join('departments', 'departments.id', '=', 'users.department_id')
                ->select('clock_in_outs.*')
                ->where('departments.name', 'like', '%' . $departmentName . '%');
        }
        return $query;
    }
}
