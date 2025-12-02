<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Task extends Model
{
    use HasFactory;
    protected $fillable = [
        'project_id',
        'created_by',
        'assigned_to',
        'title',
        'description',
        'category',
        'status',
        'priority',
        'start_date',
        'deadline',
        'progress',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'deadline' => 'date',
        'progress' => 'integer',
    ];

    /**
     * Le projet
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * L'utilisateur qui a créé la tâche
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * L'employé assigné à la tâche
     */
    public function assignedEmployee()
    {
        return $this->belongsTo(Employee::class, 'assigned_to');
    }

    /**
     * Obtenir le label du statut
     */
    public function getStatusLabelAttribute()
    {
        $statuses = [
            'a_faire' => 'À faire',
            'en_cours' => 'En cours',
            'termine' => 'Terminé',
            'bloque' => 'Bloqué',
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    /**
     * Obtenir le label de la priorité
     */
    public function getPriorityLabelAttribute()
    {
        $priorities = [
            'basse' => 'Basse',
            'moyenne' => 'Moyenne',
            'haute' => 'Haute',
            'urgente' => 'Urgente',
        ];

        return $priorities[$this->priority] ?? $this->priority;
    }

    /**
     * Vérifier si la tâche est en retard
     */
    public function isOverdue()
    {
        if (!$this->deadline) {
            return false;
        }

        return $this->status !== 'termine' && Carbon::now()->isAfter($this->deadline);
    }

    /**
     * Vérifier si la tâche est bientôt en retard (dans les 3 jours)
     */
    public function isDueSoon()
    {
        if (!$this->deadline) {
            return false;
        }

        return $this->status !== 'termine' 
            && Carbon::now()->diffInDays($this->deadline) <= 3 
            && Carbon::now()->isBefore($this->deadline);
    }

    /**
     * Scope pour un projet
     */
    public function scopeForProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Scope pour un statut
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope pour les tâches en retard
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', 'termine')
            ->where('deadline', '<', now());
    }

    /**
     * Scope pour les tâches bientôt en retard
     */
    public function scopeDueSoon($query)
    {
        return $query->where('status', '!=', 'termine')
            ->whereBetween('deadline', [now(), now()->addDays(3)]);
    }
}
