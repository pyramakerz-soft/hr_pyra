<?php
/*
 * Created At: 2026-04-30T05:26:45Z
 */

namespace Modules\Clocks\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Clocks\Http\Requests\StoreB2bFixedPermissionSlotRequest;
use Modules\Clocks\Models\B2bFixedPermissionSlot;
use Modules\Users\Models\User;

class B2bFixedPermissionSlotController extends Controller
{
    use ResponseTrait;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $userId = $request->query('user_id');
        
        $query = B2bFixedPermissionSlot::query()->with('creator:id,name');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $slots = $query->orderByDesc('created_at')->get();

        return $this->returnData('slots', $slots);
    }

    /**
     * Get the active slot for a user.
     */
    public function forUser(User $user)
    {
        $slot = B2bFixedPermissionSlot::where('user_id', $user->id)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now()->toDateString());
            })
            ->first();

        return $this->returnData('slot', $slot);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreB2bFixedPermissionSlotRequest $request)
    {
        $validated = $request->validated();

        $slot = DB::transaction(function () use ($validated) {
            // Deactivate existing active slots for this user
            B2bFixedPermissionSlot::where('user_id', $validated['user_id'])
                ->where('is_active', true)
                ->update(['is_active' => false]);

            return B2bFixedPermissionSlot::create($validated + [
                'created_by' => Auth::id(),
                'is_active' => true,
            ]);
        });

        return $this->returnData('slot', $slot, 'Fixed permission slot created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreB2bFixedPermissionSlotRequest $request, B2bFixedPermissionSlot $slot)
    {
        $validated = $request->validated();
        
        // Ensure we are updating the correct user's slot if user_id changed (though usually it won't)
        $slot->update($validated);

        return $this->returnData('slot', $slot, 'Fixed permission slot updated successfully.');
    }

    /**
     * Remove the specified resource from storage (Deactivate).
     */
    public function destroy(B2bFixedPermissionSlot $slot)
    {
        $slot->update(['is_active' => false]);

        return $this->returnSuccessMessage('Fixed permission slot deactivated successfully.');
    }
}
