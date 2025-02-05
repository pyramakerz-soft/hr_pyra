<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Tymon\JWTAuth\Facades\JWTAuth;

trait AuthTrait
{
    protected function validateUser(array $credentials)
    {
        $user = User::where('email', $credentials['email'])->first();

        if ($user && Hash::check($credentials['password'], $user->password)) {
            return $user;
        }

        return null; // Return null if user not found or password is incorrect
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
            throw new \Exception('Serial number does not match', 406);
        }

        // Handle mobile verification
        
    }
}

    protected function generateToken(Request $request, User $user)
    {
        // Attempt to generate a JWT token
        $token = JWTAuth::attempt($request->only('email', 'password'));

        if (!$token) {
            throw new \Exception('You Are unauthenticated', Response::HTTP_UNAUTHORIZED);
        }

        // Check if email or password has been updated
        $emailUpdated = $request->has('email') && $request->email !== $user->email;
        $passwordUpdated = $request->has('password') && !Hash::check($request->password, $user->password);

        // If email or password was updated, refresh the token
        if ($emailUpdated || $passwordUpdated) {
            $token = auth()->refresh();
        }

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
