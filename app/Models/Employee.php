<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'position',
        'employee_number',
        'hire_date',
        'hourly_rate',
        'address',
        'city',
        'country',
        'birth_date',
        'id_number',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'birth_date' => 'date',
        'hourly_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * L'entreprise de l'employé
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Les projets auxquels l'employé est affecté
     */
    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_employees')
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
     * Les pointages de l'employé
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Nom complet de l'employé
     */
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Scope pour les employés actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pour les employés d'une entreprise
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Vérifier si l'employé est affecté à un projet
     */
    public function isAssignedToProject($projectId)
    {
        return $this->projects()
            ->where('projects.id', $projectId)
            ->wherePivot('is_active', true)
            ->exists();
    }

    /**
     * Les tâches assignées à l'employé
     */
    public function tasks()
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }
}
