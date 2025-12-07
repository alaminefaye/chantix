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
     * IMPORTANT: Un utilisateur ne voit QUE les projets qui lui sont explicitement assignés
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

        // Si l'utilisateur n'a AUCUN projet assigné, il ne voit RIEN (sécurité)
        if (empty($assignedProjectIds)) {
            // Retourner une requête qui ne retournera jamais de résultats
            return $query->where('company_id', $companyId)
                ->whereRaw('1 = 0'); // Condition toujours fausse
        }

        // Utilisateur normal : voir SEULEMENT les projets auxquels il est explicitement associé
        // SÉCURITÉ: Ne pas inclure les projets créés par l'utilisateur sauf s'ils sont aussi dans project_user
        // Un utilisateur ne doit voir QUE les projets qui lui sont explicitement assignés
        return $query->where('company_id', $companyId)
            ->whereIn('id', $assignedProjectIds); // SEULEMENT les projets dans project_user
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
