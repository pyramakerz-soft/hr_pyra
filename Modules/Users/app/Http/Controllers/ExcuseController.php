<?php

namespace Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\Users\Http\Requests\Api\Excuses\StoreExcusesRequest;
use Modules\Users\Models\Excuse;
use Modules\Users\Models\User;

/**
 * @OA\Tag(
 *     name="Excuses",
 *     description="Operations related to user excuses"
 * )
 */
class ExcuseController extends Controller
{
    use ResponseTrait;

    /**
     * @OA\Post(
     *     path="/api/excuse/add_user_excuse",
     *     tags={"Excuses"},
     *     summary="Add a new excuse for the authenticated user",
     *     operationId="addUserExcuse",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"date", "from", "to", "status"},
     *             @OA\Property(property="date", type="string", format="date", example="2025-02-27"),
     *             @OA\Property(property="from", type="string", example="09:00"),
     *             @OA\Property(property="to", type="string", example="17:00"),
     *             @OA\Property(property="status", type="string", enum={"pending", "approved", "rejected"}, example="pending")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User Excuse created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="Excuse", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid input"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function addUserExcuse(StoreExcusesRequest $request)
    {
        $authUser = Auth::user();
        $userExcuse = Excuse::create([
            'date' => $request->input('date'),
            'from' => $request->input('from'),
            'to' => $request->input('to'),
            'status' => $request->input('status', 'pending'), // Default to 'pending' if not provided
            'user_id' => $authUser->id,
        ]);

        return $this->returnData('Excuse', $userExcuse, 'User Vacations Data');
    }

    /**
     * @OA\Get(
     *     path="/api/excuse/show_user_excuses",
     *     tags={"Excuses"},
     *     summary="Show all excuses for the authenticated user",
     *     operationId="showUserExcuses",
     *     @OA\Response(
     *         response=200,
     *         description="List of user excuses",
     *         @OA\JsonContent(
     *             @OA\Property(property="Excuses", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function showUserExcuses(Request $request)
    {
        $authUser = Auth::user();

        // Fetch and paginate the user's excuses
        $userExcuses = $authUser->excuses()
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return $this->returnData('Excuse', $userExcuses, 'User Vacations Data');
    }

    /**
     * @OA\Post(
     *     path="/api/excuse/change_excuse_status/{excuse}",
     *     tags={"Excuses"},
     *     summary="Change the status of a specific excuse",
     *     operationId="changeExcuseStatus",
     *     @OA\Parameter(
     *         name="excuse",
     *         in="path",
     *         required=true,
     *         description="Excuse ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", enum={"pending", "approved", "rejected"}, example="approved")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Excuse status updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="Excuse", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Excuse not found"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized to change status"
     *     )
     * )
     */
    public function changeExcuseStatus(Request $request, $excuseId)
    {
        $request->validate([
            'status' => 'required|in:pending,approved,rejected',
        ]);

        $excuse = Excuse::find($excuseId);

        if (!$excuse) {
            return $this->returnError('Excuse not found', 404);
        }

        $authUser = Auth::user();
        $department = $excuse->user->department;

    $managerIds = $department->managers()->pluck('users.id')->toArray();

    if (!in_array($authUser->id, $managerIds) && $excuse->user_id != $authUser->id) {
        return $this->returnError('You are not authorized to update this excuse', 403);
    }

        $excuse->status = $request->input('status');
        $excuse->save();

        return $this->returnData('Excuse', $excuse, 'Excuse status updated successfully');
    }

    /**
     * @OA\Get(
     *     path="/api/excuse/get_excuses_of_manager_employees",
     *     tags={"Excuses"},
     *     summary="Get excuses of employees in the manager's department",
     *     operationId="getExcusesOfManagerEmployees",
     *     @OA\Response(
     *         response=200,
     *         description="List of excuses for employees in the manager's department",
     *         @OA\JsonContent(
     *             @OA\Property(property="Excuses", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized"
     *     )
     * )
     */

    public function getExcusesOfManagerEmployees()
    {
        $manager = Auth::user();
    
 
    
        // Get department IDs where the user is a manager
        $departmentIds = $manager->managedDepartments()->pluck('departments.id');
    
        if ($departmentIds->isEmpty()) {
            return $this->returnError('Manager is not assigned to any department', 404);
        }
    
        // Get all employees in those departments
   $employeeIds = User::whereIn('department_id', $departmentIds)
   ->where('id', '!=', $manager->id) // Exclude the manager
   ->pluck('id');

        if ($employeeIds->isEmpty()) {
            return $this->returnError('No employees found under this manager', 404);
        }
    
        // Define the date range (26th of previous month to 26th of current month)
        $currentDate = Carbon::now();
        if ($currentDate->day > 26) {
            $startDate = $currentDate->copy()->setDay(26);
            $endDate = $currentDate->copy()->addMonth()->setDay(26);
        } else {
            $startDate = $currentDate->copy()->subMonth()->setDay(26);
            $endDate = $currentDate->copy()->setDay(26);
        }
    
        // Fetch excuses for employees in the managed departments
        $excuses = Excuse::whereIn('user_id', $employeeIds)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->get();
    
        return $this->returnData('Excuses', $excuses, 'Excuses for employees in the departments managed by the authenticated user');
    }
    
    
}
