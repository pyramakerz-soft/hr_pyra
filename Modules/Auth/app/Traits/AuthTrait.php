<?php

namespace Modules\Auth\Traits;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Modules\Users\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;

trait AuthTrait
{
    // protected function validateUser(array $credentials)
    // {
    //     $user = User::where('email', $credentials['email'])->first();

    //     if ($user && Hash::check($credentials['password'], $user->password)) {
    //         return $user;
    //     }

    //     return null; // Return null if user not found or password is incorrect
    // }

    protected function validateUser(array $credentials)
    {
        $user = User::whereRaw('LOWER(email) = ?', [strtolower($credentials['email'])])->first();

        if ($user && Hash::check($credentials['password'], $user->password)) {
            return $user;
        }

        return null;
    }



    /**
     * Validate the new_serial_number from the request for the given user.
     *
     * @param Request $request
     * @param User $user
     * @throws \Exception
     */
    protected function validateSerialNumber(Request $request, User $user)
    {
        // Ensure new_serial_number is present in the request
        if (!$request->has('new_serial_number') ) {
            return;
        }

        $serial = $request->new_serial_number;
        Log::info('Serial number from request: ' . $serial);
        Log::info($user->serial_number);
        // // Serial number must contain '#' (indicating a valid app version)
        // if (strpos($serial, '#') === false) {
        //     throw new \Exception('Please update the app to the latest version to continue.', 406);
        // }

        // If user already has a serial number, check for uniqueness and match
        if ($user->serial_number != null) {
            // If the user's serial number is different from the request, throw error
            if ($user->serial_number != $serial) {
                throw new \Exception('Serial number is Wrong.', 422);
            }
            // If serial matches, do nothing (valid)
            return;
        } else {
            // If user does not have a serial number, set it (with uniqueness check)
            $request->validate([
                'new_serial_number' => [Rule::unique('users', 'serial_number')],
            ]);
            $user->update(['serial_number' => $serial]);
        }
        // Optionally: Handle mobile verification here
    }

    protected function generateToken(Request $request, User $user)
    {
        // Convert email to lowercase before attempting authentication
        $credentials = [
            'email' => strtolower($request->email),
            'password' => $request->password
        ];

        // Attempt authentication with lowercase email
        $token = JWTAuth::attempt($credentials);

        if (!$token) {
            throw new \Exception('You are unauthenticated', Response::HTTP_UNAUTHORIZED);
        }

        // Return the generated token
        return $token;
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'result' => "true",
            'token' => $token,
        ], Response::HTTP_OK);
    }
}
