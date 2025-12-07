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
     * - Les projets qu'il a créés
     * - Les projets qui lui sont explicitement assignés dans project_user
     */
    public function scopeAccessibleByUser($query, $user, $companyId)
    {
        // Admin et super admin voient tous les projets de l'entreprise
        if ($user->isSuperAdmin() || $user->hasRoleInCompany('admin', $companyId)) {
            return $query->where('company_id', $companyId);
        }

        // Vérifier si l'utilisateur a des projets assignés dans project_user
        $assignedProjectIds = \Illuminate\Support\Facades\DB::table('project_user')
            ->where('user_id', $user->id)
            ->pluck('project_id')
            ->toArray();

        // Utilisateur normal : voir les projets qu'il a créés OU ceux qui lui sont assignés
        return $query->where('company_id', $companyId)
            ->where(function($q) use ($user, $assignedProjectIds) {
                // Projets créés par l'utilisateur
                $q->where('created_by', $user->id);
                
                // OU projets assignés dans project_user
                if (!empty($assignedProjectIds)) {
                    $q->orWhereIn('id', $assignedProjectIds);
                }
            });
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
