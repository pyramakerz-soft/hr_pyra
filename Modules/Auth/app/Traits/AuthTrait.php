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



    protected function validateSerialNumber(Request $request, User $user)
    {
        if ($request->serial_number) {

            // Check if the serial number doesn't contain "#" (indicating an outdated version)
            if (strpos($request->serial_number, '#') === false) {
                throw new \Exception('Please update the app to the latest version to continue.', 406);
            }

            if (is_null($user->serial_number)) {
                $request->validate([
                    'serial_number' => [Rule::unique('users', 'serial_number')->ignore($user->id)],
                ]);
                $user->update(['serial_number' => $request->serial_number]);
            }
            // If user already has a serial number but it doesn't contain "#", update it
            elseif (strpos($user->serial_number, '#') === false) {
                $user->update(['serial_number' => $request->serial_number]);
            }
            // If the user's serial number is different from the request serial number, throw an error
            elseif ($user->serial_number !== $request->serial_number) {
                Log::info('SERIAL COMPRISON');

                Log::info('user SERIAL '.$user->serial_number );
                Log::info('REQUEST SERIAL '.$request->serial_number);

                throw new \Exception('Serial number does not match', 406);
            }

            // Handle mobile verification

        }
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
