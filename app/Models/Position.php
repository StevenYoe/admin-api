<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// Position model represents the 'login_positions' table and its relationship with users.
class Position extends Model
{
    use HasFactory;

    // Specify the database connection and table name
    protected $connection = 'pazar';
    protected $table = 'login_positions';
    protected $primaryKey = 'pos_id';

    // Mass assignable attributes
    protected $fillable = [
        'pos_code',
        'pos_name',
        'pos_is_active',
        'pos_created_by',
        'pos_updated_by'
    ];

    // Attribute casting for boolean and datetime columns
    protected $casts = [
        'pos_is_active' => 'boolean',
        'pos_created_at' => 'datetime',
        'pos_updated_at' => 'datetime',
    ];

    // Custom column names for created_at and updated_at
    const CREATED_AT = 'pos_created_at';
    const UPDATED_AT = 'pos_updated_at';

    /**
     * Get all users that belong to this position.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function users()
    {
        return $this->hasMany(User::class, 'u_position_id', 'pos_id');
    }
}