<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $fillable = [
        'name',
        'guard_name',
        'display_name',
        'description',
        'permissions',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    /**
     * Les utilisateurs ayant ce rôle
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'company_user')
                    ->withPivot('company_id', 'is_active')
                    ->withTimestamps();
    }

    /**
     * Les entreprises où ce rôle est utilisé
     */
    public function companies()
    {
        return $this->belongsToMany(Company::class, 'company_user')
                    ->withPivot('user_id', 'is_active')
                    ->withTimestamps();
    }
}
