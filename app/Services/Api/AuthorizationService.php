<?php

namespace App\Services\Api;

use App\Traits\ResponseTrait;
use Illuminate\Auth\Access\AuthorizationException;

class AuthorizationService
{
    use ResponseTrait;
    public function authorizeHrUser($user)
    {
        if (!$user->hasRole('Hr') ||! $user->hasRole('Admin') ) {
            throw new AuthorizationException('You are not authorized to view user clocks.');
        }
    }
}
