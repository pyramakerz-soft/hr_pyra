<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;

interface IResource
{
    public function toArray(Request $request): array;
}
