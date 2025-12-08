<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'address',
        'latitude',
        'longitude',
        'start_date',
        'end_date',
        'budget',
        'status',
        'progress',
        'created_by',
        'managers',
        'client_name',
        'client_contact',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'budget' => 'decimal:2',
        'progress' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'managers' => 'array',
    ];

    /**
     * L'entreprise propriétaire du projet
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * L'utilisateur qui a créé le projet
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope pour les projets actifs
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'en_cours');
    }

    /**
     * Scope pour les projets d'une entreprise
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope pour les projets accessibles par un utilisateur
     * Un utilisateur voit :
     * - Tous les projets s'il est admin ou super admin
     * - Si des projets sont explicitement assignés dans project_user : SEULEMENT ces projets
     * - Sinon, selon le rôle :
     *   - Superviseur/Ingénieur : tous les projets de l'entreprise
     *   - Autres : projets qu'il a créés
     */
    public function scopeAccessibleByUser($query, $user, $companyId)
    {
        // Admin et super admin voient tous les projets de l'entreprise
        if ($user->isSuperAdmin() || $user->hasRoleInCompany('admin', $companyId)) {
            \Log::info('accessibleByUser: Admin/SuperAdmin - voir tous les projets', [
                'user_id' => $user->id,
                'company_id' => $companyId,
                'is_super_admin' => $user->isSuperAdmin(),
                'is_admin' => $user->hasRoleInCompany('admin', $companyId)
            ]);
            return $query->where('company_id', $companyId);
        }

        // Vérifier si l'utilisateur a des projets assignés dans project_user
        $assignedProjectIds = \Illuminate\Support\Facades\DB::table('project_user')
            ->where('user_id', $user->id)
            ->pluck('project_id')
            ->toArray();

        // Log pour débogage
        $role = $user->roleInCompany($companyId);
        $roleName = $role ? $role->name : null;
        
        \Log::info('accessibleByUser: Vérification des projets assignés', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'company_id' => $companyId,
            'role_name' => $roleName,
            'assigned_project_ids' => $assignedProjectIds,
            'assigned_count' => count($assignedProjectIds)
        ]);

        // Si l'utilisateur a des projets assignés, il ne voit QUE ces projets
        // (même s'il est superviseur ou ingénieur)
        if (!empty($assignedProjectIds)) {
            \Log::info('accessibleByUser: Projets assignés trouvés - filtrer par ces projets uniquement', [
                'user_id' => $user->id,
                'role_name' => $roleName,
                'project_ids' => $assignedProjectIds
            ]);
            return $query->where('company_id', $companyId)
                ->whereIn('id', $assignedProjectIds);
        }

        // Si aucun projet n'est assigné, vérifier le rôle
        // Superviseur et Ingénieur voient tous les projets s'ils n'ont pas de projets assignés
        if (in_array($roleName, ['superviseur', 'ingenieur'])) {
            \Log::info('accessibleByUser: Superviseur/Ingénieur sans projets assignés - voir tous les projets', [
                'user_id' => $user->id,
                'role_name' => $roleName
            ]);
            return $query->where('company_id', $companyId);
        }

        // Autres utilisateurs : voir seulement les projets qu'ils ont créés
        \Log::info('accessibleByUser: Utilisateur normal - voir seulement les projets créés', [
            'user_id' => $user->id,
            'role_name' => $roleName
        ]);
        return $query->where('company_id', $companyId)
            ->where('created_by', $user->id);
    }

    /**
     * Les mises à jour d'avancement du projet
     */
    public function progressUpdates()
    {
        return $this->hasMany(ProgressUpdate::class)->orderBy('created_at', 'desc');
    }

    /**
     * Les matériaux du projet
     */
    public function materials()
    {
        return $this->belongsToMany(Material::class, 'project_materials')
                    ->withPivot('quantity_planned', 'quantity_ordered', 'quantity_delivered', 'quantity_used', 'quantity_remaining', 'notes')
                    ->withTimestamps();
    }

    /**
     * Les relations project_materials
     */
    public function projectMaterials()
    {
        return $this->hasMany(ProjectMaterial::class);
    }

    /**
     * Les employés affectés au projet
     */
    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'project_employees')
                    ->withPivot('assigned_date', 'end_date', 'notes', 'is_active')
                    ->withTimestamps();
    }

    /**
     * Les relations project_employees
     */
    public function projectEmployees()
    {
        return $this->hasMany(ProjectEmployee::class);
    }

    /**
     * Les pointages du projet
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Les dépenses du projet
     */
    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Les tâches du projet
     */
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Les rapports du projet
     */
    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    /**
     * Les commentaires du projet
     */
    public function comments()
    {
        return $this->hasMany(Comment::class)->whereNull('parent_id')->orderBy('created_at', 'desc');
    }

    /**
     * Tous les commentaires du projet (y compris les réponses)
     */
    public function allComments()
    {
        return $this->hasMany(Comment::class)->orderBy('created_at', 'asc');
    }

    /**
     * L'historique des changements de statut
     */
    public function statusHistory()
    {
        return $this->hasMany(ProjectStatusHistory::class)->orderBy('created_at', 'desc');
    }

    /**
     * Les utilisateurs associés au projet
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'project_user')
                    ->withTimestamps();
    }
}
