<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// Division model represents the 'login_divisions' table and its relationship with users.
class Division extends Model
{
    use HasFactory;

    // Specify the database connection and table name
    protected $connection = 'pazar';
    protected $table = 'login_divisions';
    protected $primaryKey = 'div_id';

    // Mass assignable attributes
    protected $fillable = [
        'div_code',
        'div_name',
        'div_is_active',
        'div_created_by',
        'div_updated_by'
    ];

    // Attribute casting for boolean and datetime columns
    protected $casts = [
        'div_is_active' => 'boolean',
        'div_created_at' => 'datetime',
        'div_updated_at' => 'datetime',
    ];

    // Custom column names for created_at and updated_at
    const CREATED_AT = 'div_created_at';
    const UPDATED_AT = 'div_updated_at';

    /**
     * Get all users that belong to this division.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function users()
    {
        return $this->hasMany(User::class, 'u_division_id', 'div_id');
    }
}