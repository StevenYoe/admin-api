<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

// User model represents the 'login_users' table and its relationships with other models.
// It extends Authenticatable for authentication and uses Sanctum for API tokens.
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // Specify the database connection and table name
    protected $connection = 'pazar';
    protected $table = 'login_users';
    protected $primaryKey = 'u_id';

    // Custom column names for created_at and updated_at
    const CREATED_AT = 'u_created_at';
    const UPDATED_AT = 'u_updated_at';

    // Mass assignable attributes
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

    // Hidden attributes for arrays (e.g., password)
    protected $hidden = [
        'u_password',
    ];

    // Attribute casting for boolean, date, and datetime columns
    protected $casts = [
        'u_is_manager' => 'boolean',
        'u_is_active' => 'boolean',
        'u_birthdate' => 'date',
        'u_join_date' => 'date',
        'u_created_at' => 'datetime',
        'u_updated_at' => 'datetime',
    ];

    /**
     * Mutator to always store email in lowercase.
     *
     * @param string $value
     */
    public function setUEmailAttribute($value)
    {
        $this->attributes['u_email'] = strtolower($value);
    }

    /**
     * Get the division this user belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function division()
    {
        return $this->belongsTo(Division::class, 'u_division_id', 'div_id');
    }

    /**
     * Get the position this user belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function position()
    {
        return $this->belongsTo(Position::class, 'u_position_id', 'pos_id');
    }

    /**
     * Get the manager of this user (self-referencing relationship).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function manager()
    {
        return $this->belongsTo(User::class, 'u_manager_id', 'u_id');
    }

    /**
     * Get all subordinates for this user (self-referencing relationship).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function subordinates()
    {
        return $this->hasMany(User::class, 'u_manager_id', 'u_id');
    }

    /**
     * Get all roles assigned to this user (many-to-many relationship).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'login_user_roles', 'ur_user_id', 'ur_role_id');
    }
}