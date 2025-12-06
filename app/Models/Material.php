<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Material extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'category',
        'unit',
        'unit_price',
        'supplier',
        'reference',
        'stock_quantity',
        'min_stock',
        'is_active',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'stock_quantity' => 'decimal:2',
        'min_stock' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * L'entreprise propriétaire du matériau
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Les projets utilisant ce matériau
     */
    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_materials')
                    ->withPivot('quantity_planned', 'quantity_ordered', 'quantity_delivered', 'quantity_used', 'quantity_remaining', 'notes')
                    ->withTimestamps();
    }

    /**
     * Les mouvements de stock de ce matériau
     */
    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Vérifier si le stock est faible
     */
    public function isLowStock()
    {
        return $this->stock_quantity <= $this->min_stock;
    }

    /**
     * Calculer la quantité disponible (stock - utilisé dans tous les projets)
     */
    public function getAvailableQuantityAttribute()
    {
        $usedInProjects = $this->projects()
            ->sum('project_materials.quantity_used');
        
        return max(0, $this->stock_quantity - $usedInProjects);
    }

    /**
     * Scope pour les matériaux actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pour les matériaux d'une entreprise
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
