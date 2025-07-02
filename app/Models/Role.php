<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// Role model represents the 'login_roles' table and its relationship with users.
class Role extends Model
{
    use HasFactory;

    // Specify the database connection and table name
    protected $connection = 'pazar';
    protected $table = 'login_roles';
    protected $primaryKey = 'role_id';

    // Mass assignable attributes
    protected $fillable = [
        'role_name',
        'role_level',
        'role_is_active',
        'role_created_by',
        'role_updated_by'
    ];

    // Attribute casting for boolean and datetime columns
    protected $casts = [
        'role_is_active' => 'boolean',
        'role_created_at' => 'datetime',
        'role_updated_at' => 'datetime',
    ];

    // Custom column names for created_at and updated_at
    const CREATED_AT = 'role_created_at';
    const UPDATED_AT = 'role_updated_at';

    /**
     * Get all users that have this role (many-to-many relationship).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'login_user_roles', 'ur_role_id', 'ur_user_id');
    }
}