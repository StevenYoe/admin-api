<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    // Remove schema prefix from table name
    protected $connection = 'pazar';
    protected $table = 'login_roles';
    protected $primaryKey = 'role_id';

    protected $fillable = [
        'role_name',
        'role_level',
        'role_is_active',
        'role_created_by',
        'role_updated_by'
    ];

    protected $casts = [
        'role_is_active' => 'boolean',
        'role_created_at' => 'datetime',
        'role_updated_at' => 'datetime',
    ];

    // Column name mappings
    const CREATED_AT = 'role_created_at';
    const UPDATED_AT = 'role_updated_at';

    public function users()
    {
        return $this->belongsToMany(User::class, 'login_user_roles', 'ur_role_id', 'ur_user_id');
    }
}