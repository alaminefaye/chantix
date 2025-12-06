<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $fillable = [
        'material_id',
        'project_id',
        'user_id',
        'type',
        'quantity',
        'stock_before',
        'stock_after',
        'reason',
        'notes',
        'reference',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'stock_before' => 'decimal:2',
        'stock_after' => 'decimal:2',
    ];

    /**
     * Le matériau concerné
     */
    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    /**
     * Le projet concerné (si applicable)
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * L'utilisateur qui a effectué le mouvement
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
