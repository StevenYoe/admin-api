<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    use HasFactory;

    // Remove schema prefix from table name
    protected $connection = 'mysql';
    protected $table = 'login_positions';
    protected $primaryKey = 'pos_id';

    protected $fillable = [
        'pos_code',
        'pos_name',
        'pos_is_active',
        'pos_created_by',
        'pos_updated_by'
    ];

    protected $casts = [
        'pos_is_active' => 'boolean',
        'pos_created_at' => 'datetime',
        'pos_updated_at' => 'datetime',
    ];

    // Column name mappings
    const CREATED_AT = 'pos_created_at';
    const UPDATED_AT = 'pos_updated_at';

    public function users()
    {
        return $this->hasMany(User::class, 'u_position_id', 'pos_id');
    }
}