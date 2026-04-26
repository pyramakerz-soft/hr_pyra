<?php

namespace Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Modules\Users\Models\SalaryAdjustment;
use Modules\Users\Models\User;
use Modules\Users\Exports\SalaryAdjustmentExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
// use Maatwebsite\Excel\Facades\Excel; // If you have excel installed

class SalaryAdjustmentController extends Controller
{
    use ResponseTrait;

    public function index(Request $request)
    {
        $query = SalaryAdjustment::with('user:id,name,code');

        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('adjustment_date', [$request->from_date, $request->to_date]);
        } else {
            // Default logic: 26th of previous month to 25th of current month
            $today = Carbon::today();
            if ($today->day >= 26) {
                $startDate = $today->copy()->day(26);
                $endDate = $today->copy()->addMonth()->day(25);
            } else {
                $startDate = $today->copy()->subMonth()->day(26);
                $endDate = $today->copy()->day(25);
            }
            $query->whereBetween('adjustment_date', [$startDate, $endDate]);
        }

        $adjustments = $query->latest('adjustment_date')->paginate($request->get('per_page', 10));

        return $this->returnData('adjustments', $adjustments, 'Adjustments retrieved successfully');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric',
            'reason' => 'required|string|max:255',
            'adjustment_date' => 'required|date',
        ]);

        $adjustment = SalaryAdjustment::create($validated);

        return $this->returnData('adjustment', $adjustment, 'Adjustment added successfully');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'user_id' => 'sometimes|required|exists:users,id',
            'amount' => 'sometimes|required|numeric',
            'reason' => 'sometimes|required|string|max:255',
            'adjustment_date' => 'sometimes|required|date',
        ]);

        $adjustment = SalaryAdjustment::findOrFail($id);
        $adjustment->update($validated);

        return $this->returnData('adjustment', $adjustment, 'Adjustment updated successfully');
    }

    public function destroy($id)
    {
        $adjustment = SalaryAdjustment::findOrFail($id);
        $adjustment->delete();

        return $this->returnSuccessMessage('Adjustment deleted successfully');
    }

    public function export(Request $request)
    {
        $fromDate = $request->from_date;
        $toDate = $request->to_date;

        if (!$fromDate || !$toDate) {
            $today = Carbon::today();
            if ($today->day >= 26) {
                $fromDate = $today->copy()->day(26)->toDateString();
                $toDate = $today->copy()->addMonth()->day(25)->toDateString();
            } else {
                $fromDate = $today->copy()->subMonth()->day(26)->toDateString();
                $toDate = $today->copy()->day(25)->toDateString();
            }
        }

        return Excel::download(new SalaryAdjustmentExport($fromDate, $toDate), "Salary_Adjustments_{$fromDate}_to_{$toDate}.xlsx");
    }
}
