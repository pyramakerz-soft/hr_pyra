<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLocationRequest;
use App\Http\Requests\UpdateLocationRequest;
use App\Models\Location;
use App\Models\User;
use App\Traits\ResponseTrait;

/**
 * @OA\Schema(
 *   schema="Location",
 *   type="object",
 *   required={"name", "address", "latitude", "longitude", "start_time", "end_time"},
 *   @OA\Property(property="id", type="integer", example=1),
 *   @OA\Property(property="name", type="string", example="New York"),
 *   @OA\Property(property="address", type="string", example="123 Main St, New York, NY"),
 *   @OA\Property(property="latitude", type="number", format="float", example=31.2403970),
 *   @OA\Property(property="longitude", type="number", format="float", example=29.9660127),
 *   @OA\Property(property="start_time", type="string", format="date-time", example="07:00"),
 *   @OA\Property(property="end_time", type="string", format="date-time", example="15:00"),
 *   @OA\Property(property="created_at", type="string", format="date-time", example="2024-10-01T12:00:00Z"),
 *   @OA\Property(property="updated_at", type="string", format="date-time", example="2024-10-01T12:30:00Z")
 * )
 */
class LocationController extends Controller
{
    use ResponseTrait;
    public function __construct()
    {
        // $this->middleware("permission:location-list")->only(['index', 'locationNames', 'show']);
        // $this->middleware("permission:location-create")->only(['store']);
        // $this->middleware("permission:location-edit")->only(['update']);
        // $this->middleware("permission:location-delete")->only(['destroy']);
    }

    /**
     * @OA\Get(
     *   path="/api/locations",
     *   summary="Get a list of locations",
     *   tags={"Location"},
     *   security={{"bearerAuth": {}}},
     *   @OA\Parameter(
     *     name="search",
     *     in="query",
     *     description="Search term for location name",
     *     required=false,
     *     @OA\Schema(type="string")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="List of locations",
     *     @OA\JsonContent(
     *       type="array",
     *       @OA\Items(ref="#/components/schemas/Location")
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="No locations found",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="No locations Found")
     *     )
     *   )
     * )
     */
    public function index()
    {

        if (request()->has('search')) {
            $locations = Location::where('name', 'like', '%' . request()->get('search', '') . '%')->get();
        } else {
            $locations = Location::paginate(5);
        }
        if ($locations->isEmpty()) {
            return $this->returnError('No locations Found');
        }
        return $this->returnData('locations', $locations, 'Locations Data');
    }
    /**
     * @OA\Get(
     *   path="/api/location_names",
     *   summary="Get list of location names",
     *   tags={"Location"},
     *   security={{"bearerAuth": {}}},
     *   @OA\Response(
     *     response=200,
     *     description="List of location names",
     *     @OA\JsonContent(
     *       type="array",
     *       @OA\Items(
     *         @OA\Property(property="id", type="integer", example=1),
     *         @OA\Property(property="name", type="string", example="New York")
     *       )
     *     )
     *   )
     * )
     */
    public function locationNames()
    {
        $locationNames = Location::get()->map(function ($location) {
            return ['id' => $location->id, 'name' => $location->name];
        })->toArray();
        return $this->returnData('locationNames', $locationNames, 'Location Names');
    }
    /**
     * @OA\Post(
     *   path="/api/locations",
     *   summary="Create a new location",
     *   tags={"Location"},
     *   security={{"bearerAuth": {}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       @OA\Property(property="name", type="string", example="New York"),
     *       @OA\Property(property="address", type="string", example="123 Main St, New York, NY"),
     *       @OA\Property(property="latitude", type="number", format="float", example=31.2403970),
     *       @OA\Property(property="longitude", type="number", format="float", example=-29.9660127),
     *       @OA\Property(property="start_time", type="string", format="time", example="07:00"),
     *       @OA\Property(property="end_time", type="string", format="time", example="15:00")
     *     )
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Location stored successfully",
     *     @OA\JsonContent(ref="#/components/schemas/Location")
     *   ),
     *   @OA\Response(
     *     response=400,
     *     description="Validation error",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Validation Error")
     *     )
     *   )
     * )
     */
    public function store(StoreLocationRequest $request)
    {
        $location = Location::create($request->validated());
        return $this->returnData('location', $location, 'Location Stored Successfully');

    }
    /**
     * @OA\Get(
     *   path="/api/locations/{location}",
     *   summary="Get a specific location",
     *   tags={"Location"},
     *   security={{"bearerAuth": {}}},
     *   @OA\Parameter(
     *     name="location",
     *     in="path",
     *     description="ID of the location",
     *     required=true,
     *     @OA\Schema(type="integer", example=1)
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Location details",
     *     @OA\JsonContent(ref="#/components/schemas/Location")
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Location not found",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Location Not Found")
     *     )
     *   )
     * )
     */
    public function show(Location $location)
    {
        return $this->returnData('location', $location, 'Location Data');

    }

    /**
     * @OA\Post(
     *   path="/api/locations/{location}",
     *   summary="Update a location",
     *   tags={"Location"},
     *   security={{"bearerAuth": {}}},
     *   @OA\Parameter(
     *     name="location",
     *     in="path",
     *     description="ID of the location",
     *     required=true,
     *     @OA\Schema(type="integer", example=1)
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       @OA\Property(property="name", type="string", example="San Francisco"),
     *       @OA\Property(property="address", type="string", example="456 Elm St, San Francisco, CA"),
     *       @OA\Property(property="latitude", type="number", format="float", example=31.2403970),
     *       @OA\Property(property="longitude", type="number", format="float", example=29.9660127),
     *       @OA\Property(property="start_time", type="string", format="date-time", example="07:00"),
     *       @OA\Property(property="end_time", type="string", format="date-time", example="15:00")
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Location updated successfully",
     *     @OA\JsonContent(ref="#/components/schemas/Location")
     *   ),
     *   @OA\Response(
     *     response=400,
     *     description="Validation error",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Validation Error")
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Location not found",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Location Not Found")
     *     )
     *   )
     * )
     */
    public function update(UpdateLocationRequest $request, Location $location)
    {
        $location->update($request->validated());
        return $this->returnData('location', $location, 'Location updated Successfully');
    }
    /**
     * @OA\Delete(
     *   path="/api/locations/{location}",
     *   summary="Delete a location",
     *   tags={"Location"},
     *   security={{"bearerAuth": {}}},
     *   @OA\Parameter(
     *     name="location",
     *     in="path",
     *     description="ID of the location",
     *     required=true,
     *     @OA\Schema(type="integer", example=1)
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Location deleted successfully",
     *     @OA\JsonContent(ref="#/components/schemas/Location")
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Location not found",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Location Not Found")
     *     )
     *   )
     * )
     */
    public function destroy(Location $location)
    {
        $location->delete();
        return $this->returnData('location', $location, 'Location deleted Successfully');
    }
    /**
     * @OA\Get(
     *     path="/api/users/{user}/locations",
     *     tags={"Location"},
     *     summary="Get Locations Assigned to a User",
     *     description="Retrieve all locations assigned to a specific user based on their user ID.",
     *     operationId="getLocationAssignedToUser",
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             example=1
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User Locations Data",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 example="success"
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Main Office")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="User Locations Data"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 example="error"
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="User not found"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 example="error"
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="An error occurred while retrieving user locations."
     *             )
     *         )
     *     ),
     *     security={{ "bearerAuth": {} }}
     * )
     */
    public function getLocationAssignedToUser(User $user)
    {
        $users = User::with('user_locations')->where('id', $user->id)->get();
        $data = [];
        foreach ($users as $user) {
            foreach ($user->user_locations as $user_location) {
                $data[] = [
                    'id' => $user_location->id,
                    'name' => $user_location->name,
                ];

            }
        }
        return $this->returnData('user_locations', $data, 'User Locations Data');
    }
}
