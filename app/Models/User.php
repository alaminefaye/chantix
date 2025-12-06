<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar',
        'current_company_id',
        'is_super_admin',
        'is_verified',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
            'is_verified' => 'boolean',
        ];
    }

    /**
     * Les entreprises auxquelles l'utilisateur appartient
     */
    public function companies()
    {
        return $this->belongsToMany(Company::class, 'company_user')
                    ->withPivot('role_id', 'is_active', 'joined_at')
                    ->withTimestamps();
    }

    /**
     * L'entreprise actuellement sélectionnée
     */
    public function currentCompany()
    {
        return $this->belongsTo(Company::class, 'current_company_id');
    }

    /**
     * Les rôles de l'utilisateur dans les entreprises
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'company_user')
                    ->withPivot('company_id', 'is_active')
                    ->withTimestamps();
    }

    /**
     * Le rôle de l'utilisateur dans une entreprise spécifique
     */
    public function roleInCompany($companyId = null)
    {
        $companyId = $companyId ?? $this->current_company_id;
        
        if (!$companyId) {
            return null;
        }
        
        $pivot = $this->companies()
            ->where('companies.id', $companyId)
            ->first()
            ?->pivot;
        
        if (!$pivot || !$pivot->role_id) {
            return null;
        }
        
        return Role::find($pivot->role_id);
    }

    /**
     * Obtenir le rôle actuel de l'utilisateur (dans l'entreprise actuelle)
     */
    public function currentRole()
    {
        if (!$this->current_company_id) {
            return null;
        }
        
        return $this->roleInCompany($this->current_company_id);
    }

    /**
     * Vérifier si l'utilisateur a un rôle spécifique dans une entreprise
     */
    public function hasRoleInCompany($roleName, $companyId = null)
    {
        $companyId = $companyId ?? $this->current_company_id;
        
        if (!$companyId) {
            return false;
        }
        
        $role = $this->roleInCompany($companyId);
        
        return $role && $role->name === $roleName;
    }

    /**
     * Vérifier si l'utilisateur a une permission spécifique
     */
    public function hasPermission($permission, $companyId = null)
    {
        // Super admin a toutes les permissions
        if ($this->isSuperAdmin()) {
            return true;
        }
        
        $companyId = $companyId ?? $this->current_company_id;
        
        if (!$companyId) {
            return false;
        }
        
        $role = $this->roleInCompany($companyId);
        
        if (!$role) {
            return false;
        }
        
        // Admin a toutes les permissions
        if ($role->name === 'admin') {
            return true;
        }
        
        $permissions = $role->permissions ?? [];
        
        // Vérifier si la permission est dans la liste ou si c'est '*'
        return in_array('*', $permissions) || in_array($permission, $permissions);
    }

    /**
     * Vérifier si l'utilisateur peut effectuer une action sur un projet
     */
    public function canManageProject($project, $action = 'view')
    {
        $companyId = $this->current_company_id;
        
        if (!$companyId || $project->company_id !== $companyId) {
            return false;
        }
        
        // Admin peut tout faire
        if ($this->hasRoleInCompany('admin', $companyId)) {
            return true;
        }
        
        $role = $this->roleInCompany($companyId);
        if (!$role) {
            return false;
        }
        
        $permissions = $role->permissions ?? [];
        
        // Permissions selon le rôle
        switch ($role->name) {
            case 'chef_chantier':
                return in_array('projects.' . $action, $permissions) || in_array('*', $permissions);
            case 'superviseur':
                // Le superviseur peut voir et valider, mais pas créer ni modifier
                return ($action === 'view' || $action === 'validate') && in_array('projects.view', $permissions);
            case 'ingenieur':
                return $action === 'view' || in_array('projects.view', $permissions);
            case 'client':
                // Le client peut seulement voir les projets (lecture seule)
                return $action === 'view' && in_array('projects.view', $permissions);
            case 'ouvrier':
                return $action === 'view' && in_array('projects.view', $permissions);
            case 'comptable':
                return $action === 'view' || $action === 'expenses';
            default:
                return false;
        }
    }

    /**
     * Les projets créés par l'utilisateur
     */
    public function createdProjects()
    {
        return $this->hasMany(Project::class, 'created_by');
    }

    /**
     * Les projets auxquels l'utilisateur a accès
     */
    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_user')
                    ->withTimestamps();
    }

    /**
     * Les notifications de l'utilisateur
     */
    public function notifications()
    {
        return $this->hasMany(\App\Models\Notification::class)->orderBy('created_at', 'desc');
    }

    /**
     * Les tokens FCM de l'utilisateur
     */
    public function fcmTokens()
    {
        return $this->hasMany(FcmToken::class);
    }

    /**
     * Les tokens FCM actifs de l'utilisateur
     */
    public function activeFcmTokens()
    {
        return $this->fcmTokens()->where('is_active', true);
    }

    /**
     * Les notifications non lues
     */
    public function unreadNotifications()
    {
        return $this->notifications()->where('is_read', false);
    }

    /**
     * Compter les notifications non lues
     */
    public function unreadNotificationsCount()
    {
        return $this->unreadNotifications()->count();
    }

    /**
     * Vérifier si l'utilisateur est super admin
     */
    public function isSuperAdmin()
    {
        return $this->is_super_admin === true;
    }

    /**
     * Vérifier si l'utilisateur peut accéder à une ressource d'une entreprise
     * Le super admin peut toujours accéder
     */
    public function canAccessCompanyResource($companyId)
    {
        // Super admin peut accéder à tout
        if ($this->isSuperAdmin()) {
            return true;
        }
        
        // Vérifier que l'utilisateur appartient à l'entreprise
        if (!$companyId) {
            return false;
        }
        
        // Convertir en entier pour éviter les problèmes de type
        $companyId = (int) $companyId;
        
        // Vérifier si l'utilisateur appartient réellement à cette entreprise
        // Utiliser get() puis vérifier dans la collection pour éviter les problèmes SQL
        $userCompanyIds = $this->companies()->get()->pluck('id')->map(function ($id) {
            return (int) $id; // S'assurer que tous les IDs sont des entiers
        })->toArray();
        
        $result = in_array($companyId, $userCompanyIds, true); // strict comparison
        
        // Log pour déboguer si nécessaire
        if (!$result) {
            \Log::debug('canAccessCompanyResource failed', [
                'user_id' => $this->id,
                'requested_company_id' => $companyId,
                'user_company_ids' => $userCompanyIds,
                'type_company_id' => gettype($companyId),
            ]);
        }
        
        return $result;
    }

    /**
     * Vérifier si l'utilisateur est vérifié
     */
    public function isVerified()
    {
        // Super admin est toujours vérifié
        if ($this->isSuperAdmin()) {
            return true;
        }
        
        // Récupérer la valeur brute depuis la base de données pour éviter les problèmes de cast
        $isVerifiedRaw = $this->getRawOriginal('is_verified');
        $isVerified = $isVerifiedRaw === 1 
                   || $isVerifiedRaw === true 
                   || $isVerifiedRaw === '1'
                   || (bool) $isVerifiedRaw === true;
        
        // Aussi vérifier email_verified_at pour être sûr
        $hasEmailVerified = $this->email_verified_at !== null;
        
        return $isVerified || $hasEmailVerified;
    }
}
