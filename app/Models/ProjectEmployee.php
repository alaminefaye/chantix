<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectEmployee extends Model
{
    protected $fillable = [
        'project_id',
        'employee_id',
        'assigned_date',
        'end_date',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'assigned_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Le projet
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * L'employé
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
