<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectMaterial extends Model
{
    protected $fillable = [
        'project_id',
        'material_id',
        'quantity_planned',
        'quantity_ordered',
        'quantity_delivered',
        'quantity_used',
        'quantity_remaining',
        'notes',
    ];

    protected $casts = [
        'quantity_planned' => 'decimal:2',
        'quantity_ordered' => 'decimal:2',
        'quantity_delivered' => 'decimal:2',
        'quantity_used' => 'decimal:2',
        'quantity_remaining' => 'decimal:2',
    ];

    /**
     * Le projet
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Le matériau
     */
    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    /**
     * Calculer automatiquement la quantité restante
     */
    public function calculateRemaining()
    {
        $this->quantity_remaining = max(0, $this->quantity_delivered - $this->quantity_used);
        $this->save();
    }

    /**
     * Vérifier si surconsommation
     */
    public function isOverConsumption()
    {
        return $this->quantity_used > $this->quantity_planned;
    }

    /**
     * Pourcentage d'utilisation
     */
    public function getUsagePercentageAttribute()
    {
        if ($this->quantity_planned == 0) {
            return 0;
        }
        return min(100, ($this->quantity_used / $this->quantity_planned) * 100);
    }
}
