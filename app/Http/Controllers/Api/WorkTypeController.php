<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkType;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;

class WorkTypeController extends Controller
{
    use ResponseTrait;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $workTypes = WorkType::all();
        if ($workTypes->isEmpty()) {
            return $this->returnError('No WorkTypes Found');
        }
        return $this->returnData('workTypes', $workTypes, ' WorkTypes Data');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string',
        ]);
        $workType = WorkType::create([
            'name' => $request->name,
        ]);
        return $this->returnData('WorkType', $workType, 'Work Type Stored Successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(WorkType $workType)
    {
        return $this->returnData('WorkType', $workType, 'Work Type Data');

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, WorkType $workType)
    {
        $this->validate($request, [
            'name' => 'nullable|string',
        ]);
        $workType->update([
            'name' => $request->name,
        ]);
        return $this->returnData('WorkType', $workType, 'Work Type updated Successfully');

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(WorkType $workType)
    {
        $workType->delete();
        return $this->returnData('WorkType', $workType, 'Work Type deleted Successfully');

    }
}
