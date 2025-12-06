<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;
    protected $fillable = [
        'project_id',
        'created_by',
        'type',
        'title',
        'description',
        'amount',
        'expense_date',
        'supplier',
        'invoice_number',
        'invoice_date',
        'invoice_file',
        'material_id',
        'employee_id',
        'notes',
        'is_paid',
        'paid_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
        'invoice_date' => 'date',
        'paid_date' => 'date',
        'is_paid' => 'boolean',
    ];

    /**
     * Le projet
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * L'utilisateur qui a créé la dépense
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Le matériau (si type = matériaux)
     */
    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    /**
     * L'employé (si type = main-d'œuvre)
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Obtenir le label du type
     */
    public function getTypeLabelAttribute()
    {
        $types = [
            'materiaux' => 'Matériaux',
            'transport' => 'Transport',
            'main_oeuvre' => 'Main-d\'œuvre',
            'location' => 'Location machines',
            'autres' => 'Autres',
        ];

        return $types[$this->type] ?? $this->type;
    }

    /**
     * Scope pour un type spécifique
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope pour un projet
     */
    public function scopeForProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Scope pour les dépenses payées
     */
    public function scopePaid($query)
    {
        return $query->where('is_paid', true);
    }

    /**
     * Scope pour les dépenses non payées
     */
    public function scopeUnpaid($query)
    {
        return $query->where('is_paid', false);
    }

    /**
     * Résoudre le modèle pour le route model binding
     * Permet de résoudre la dépense même dans un contexte de projet
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $field = $field ?: $this->getRouteKeyName();
        
        // Recherche normale par ID (par défaut)
        $query = $this->where($field, $value);
        
        // Si un project_id est disponible dans la requête (via la route parente),
        // on peut l'utiliser pour une recherche plus précise
        // Mais on ne le fait pas ici car le contrôleur vérifie déjà cette relation
        
        return $query->first();
    }
}
