<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    use HasFactory;

    // Remove schema prefix from table name
    protected $connection = 'pazar';
    protected $table = 'login_divisions';
    protected $primaryKey = 'div_id';

    protected $fillable = [
        'div_code',
        'div_name',
        'div_is_active',
        'div_created_by',
        'div_updated_by'
    ];

    protected $casts = [
        'div_is_active' => 'boolean',
        'div_created_at' => 'datetime',
        'div_updated_at' => 'datetime',
    ];

    // Column name mappings
    const CREATED_AT = 'div_created_at';
    const UPDATED_AT = 'div_updated_at';

    public function users()
    {
        return $this->hasMany(User::class, 'u_division_id', 'div_id');
    }
}