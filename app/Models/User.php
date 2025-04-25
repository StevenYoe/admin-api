<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // In User.php and other related models
    protected $connection = 'login';
    protected $table = 'login.users';
    protected $primaryKey = 'u_id';

    const CREATED_AT = 'u_created_at';
    const UPDATED_AT = 'u_updated_at';

    protected $fillable = [
        'u_employee_id',
        'u_name',
        'u_email',
        'u_password',
        'u_phone',
        'u_address',
        'u_birthdate',
        'u_join_date',
        'u_profile_image',
        'u_division_id',
        'u_position_id',
        'u_is_manager',
        'u_manager_id',
        'u_is_active',
        'u_created_by',
        'u_updated_by'
    ];

    protected $hidden = [
        'u_password',
    ];

    protected $casts = [
        'u_is_manager' => 'boolean',
        'u_is_active' => 'boolean',
        'u_birthdate' => 'date',
        'u_join_date' => 'date',
        'u_created_at' => 'datetime',
        'u_updated_at' => 'datetime',
    ];

    // Mutator untuk u_email - Mengubah email menjadi lowercase
    public function setUEmailAttribute($value)
    {
        $this->attributes['u_email'] = strtolower($value);
    }

    public function division()
    {
        return $this->belongsTo(Division::class, 'u_division_id', 'div_id');
    }

    public function position()
    {
        return $this->belongsTo(Position::class, 'u_position_id', 'pos_id');
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'u_manager_id', 'u_id');
    }

    public function subordinates()
    {
        return $this->hasMany(User::class, 'u_manager_id', 'u_id');
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'login.user_roles', 'ur_user_id', 'ur_role_id');
    }
}