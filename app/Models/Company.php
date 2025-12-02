<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'city',
        'country',
        'logo',
        'siret',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Les utilisateurs de l'entreprise
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'company_user')
                    ->withPivot('role_id', 'is_active', 'joined_at')
                    ->withTimestamps();
    }

    /**
     * Les projets de l'entreprise
     */
    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Les utilisateurs actifs de l'entreprise
     */
    public function activeUsers()
    {
        return $this->users()->wherePivot('is_active', true);
    }

    /**
     * Les invitations de l'entreprise
     */
    public function invitations()
    {
        return $this->hasMany(Invitation::class);
    }
}
