<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = [
        'name',
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
