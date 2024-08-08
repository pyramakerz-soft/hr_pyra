<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserVacationRequest;
use App\Http\Requests\UpdateUserVacationRequest;
use App\Models\UserVacation;

class UserVacationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $userVacations = UserVacation::all();
        dd($userVacations->toArray());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserVacationRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(UserVacation $userVacation)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(UserVacation $userVacation)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserVacationRequest $request, UserVacation $userVacation)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserVacation $userVacation)
    {
        //
    }
}
