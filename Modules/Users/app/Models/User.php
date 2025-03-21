<?php

namespace Modules\Users\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
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
        'parent_manager_id' // Added for manager hierarchy

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

    public function managedDepartments()
    {
        return $this->belongsToMany(Department::class, 'department_managers', 'manager_id', 'department_id');
    }
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
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

    public function allParentManagers()
    {
        $managers = collect();
        $currentManager = $this->parentManager;
    
        while ($currentManager) {
            $managers->push($currentManager);
            $currentManager = $currentManager->parentManager;
        }
    
        return $managers;
    }
    
    /**
     * Get the direct superior (manager) of the user.
     */
    public function parentManager()
    {
        return $this->belongsTo(User::class, 'parent_manager_id');
    }
   
        /**
     * Get all subordinates (direct reports) of the user.
     */
    // employees el taht manager
    public function subordinates()
    {
        return $this->hasMany(User::class, 'parent_manager_id');
    }

}
