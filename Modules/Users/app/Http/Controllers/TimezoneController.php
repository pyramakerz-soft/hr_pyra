<?php

namespace Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Modules\Users\Models\Timezone;



class TimezoneController extends Controller
{
    use ResponseTrait;
    public function __construct()
    {
        // Apply the 'auth:api' middleware for token validation to all methods in this controller
        $this->middleware('auth:api');
    }

    /**
     * @OA\Get(
     *     path="/api/timezones",
     *     summary="Get all timezones",
     *     description="Returns a list of all timezones.",
     *     tags={"Timezones"},
     *     @OA\Response(
     *         response=200,
     *         description="A list of timezones.",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 required={"id", "name", "value"},
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Egypt"),
     *                 @OA\Property(property="value", type="integer", example=3)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Bad Request")
     * )
     */
    public function index()
    {
        $timezones = Timezone::all();
        return response()->json($timezones);
    }

    /**
     * @OA\Get(
     *     path="/api/timezones/{id}",
     *     summary="Get a specific timezone by ID",
     *     description="Returns a specific timezone based on the ID provided.",
     *     tags={"Timezones"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Timezone ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="A timezone object.",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="clock", ref="#/components/schemas/ClockInOut"),
     *             @OA\Property(property="message", type="string", example="Clock Data")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Timezone not found")
     * )
     */
    public function show($id)
    {
        $timezone = Timezone::findOrFail($id);
        return $this->returnData('timezones', $timezone, 'timezones Data');
    }

    /**
     * @OA\Post(
     *     path="/api/timezones",
     *     summary="Create a new timezone",
     *     description="Creates a new timezone and stores it in the database.",
     *     tags={"Timezones"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "value"},
     *             @OA\Property(property="name", type="string", example="Egypt"),
     *             @OA\Property(property="value", type="integer", example=3)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Timezone created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             required={"id", "name", "value"},
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Egypt"),
     *             @OA\Property(property="value", type="integer", example=3)
     *         )
     *     ),
     *     @OA\Response(response=400, description="Invalid input")
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'value' => 'required|integer',
        ]);

        $timezone = Timezone::create($request->all());
        return $this->returnData('timezones', $timezone, 'timezones Data');
    }

    /**
     * @OA\Put(
     *     path="/api/timezones/{id}",
     *     summary="Update an existing timezone",
     *     description="Updates an existing timezone's name and value.",
     *     tags={"Timezones"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Timezone ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "value"},
     *             @OA\Property(property="name", type="string", example="Saudi Arabia"),
     *             @OA\Property(property="value", type="integer", example=3)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Timezone updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             required={"id", "name", "value"},
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Egypt"),
     *             @OA\Property(property="value", type="integer", example=3)
     *         )
     *     ),
     *     @OA\Response(response=404, description="Timezone not found")
     * )
     */
    public function update(Request $request, $id)
    {
        $timezone = Timezone::findOrFail($id);

        $request->validate([
            'name' => 'required|string',
            'value' => 'required|integer',
        ]);

        $timezone->update($request->all());
        return $this->returnData('timezones', $timezone, 'timezones Data');
    }

    /**
     * @OA\Delete(
     *     path="/api/timezones/{id}",
     *     summary="Delete a timezone",
     *     description="Deletes a specific timezone by ID.",
     *     tags={"Timezones"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Timezone ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Timezone deleted successfully",
     *         @OA\JsonContent(type="object", @OA\Property(property="message", type="string", example="Timezone deleted successfully"))
     *     ),
     *     @OA\Response(response=404, description="Timezone not found")
     * )
     */
    public function destroy($id)
    {
        $timezone = Timezone::findOrFail($id);
        $timezone->delete();

        return $this->returnData('message', 'Timezone deleted successfully');
    }
}
