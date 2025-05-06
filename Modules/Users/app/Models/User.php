<?php

namespace Modules\Users\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\HasApiTokens;
use Modules\Clocks\Models\ClockInOut;
use Modules\Clocks\Models\OverTimeInOut;
use Modules\Location\Models\Location;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'contact_phone',
        'national_id',
        'code',
        'gender',
        'department_id',
        'image',
        'serial_number',
        'mob',
        'sub_department_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function timezone()
    {
        return $this->belongsTo(Timezone::class);
    }

    // Function to get employees under the given manager
    public function getManagedEmployeeIds()
    {

        $role = $this->getRoleName(); // Get the role of the authenticated user
        if ($role === 'Team leader') {
            // Check if the model's sub_department_id is set
            $subDepartmentId = $this->sub_department_id;

            if (!$subDepartmentId) {
                // Try to find a sub department where this user is the team leader
                $subDepartment = SubDepartment::where('teamlead_id', $this->id)->first();

                if (!$subDepartment) {
                    abort(406, 'No sub department assigned or led by this user.');
                }

                $subDepartmentId = $subDepartment->id;
            }



            // Get all employees in the same sub-department, excluding the team lead (this user)
            return self::where('sub_department_id', $subDepartmentId)
                ->where('id', '!=', $this->id)
                ->pluck('id');
        } elseif ($role === 'Manager' || $role === 'Hr') {
            // Check or retrieve department_id
            $department_id = $this->department_id;

            if (!$department_id) {
                $dept = Department::where('manager_id', $this->id)->first();

                if (!$dept) {
                    abort(406, 'Department is null in manager data');
                }

                $department_id = $dept->id;
            }

            // Get all team leaders in the department
            $teamLeadIds = self::where('department_id', $department_id)
                ->whereHas('roles', fn($query) => $query->where('name', 'Team leader'))
                ->pluck('id');

            // Get sub-departments in the same department
            $subDepartmentIds = SubDepartment::where('department_id', $department_id)->pluck('id');

            // Get all employees in sub-departments (including team leaders of sub-departments)
            $subDeptEmployeeIds = self::whereIn('sub_department_id', $subDepartmentIds)
                ->pluck('id');

            // Get all employees in the same department (excluding current user)
            $departmentEmployeeIds = self::where('department_id', $department_id)
                ->where('id', '!=', $this->id)
                ->pluck('id');

            // Merge all into one collection
            $data = $teamLeadIds
                ->merge($subDeptEmployeeIds)
                ->merge($departmentEmployeeIds)
                ->unique(); // prevent duplicates

            return $data;
        }

        return collect(); // Return an empty collection if the user is neither a team lead nor a manager
    }



    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }


    public function subDepartment()
    {
        return $this->belongsTo(SubDepartment::class, 'sub_department_id');
    }


    public function managers()
    {
        return $this->belongsToMany(User::class, 'user_managers', 'user_id', 'manager_id');
    }



    public function user_detail()
    {
        return $this->hasOne(UserDetail::class);
    }

    public function user_locations()
    {
        return $this->belongsToMany(Location::class, 'user_locations', 'user_id', 'location_id')->withTimestamps();
    }
    public function user_requests()
    {
        return $this->hasMany(Request::class);
    }
    public function user_clocks()
    {
        return $this->hasMany(ClockInOut::class);
    }

    public function work_types()
    {
        return $this->belongsToMany(WorkType::class, 'user_work_type', 'user_id', 'work_type_id')->withTimestamps();
    }
    public function getRoleName()
    {
        return $this->getRoleNames()->first(); // Get the first role name
    }


    public function user_vacations()
    {
        return $this->hasMany(UserVacation::class);
    }
    public function excuses()
    {
        return $this->hasMany(Excuse::class);  // A user can have many excuses
    }

    public function overTimes()
    {
        return $this->hasMany(OverTime::class);  // A user can have many excuses
    }
}
