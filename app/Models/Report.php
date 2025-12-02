<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = [
        'project_id',
        'created_by',
        'type',
        'report_date',
        'end_date',
        'data',
        'file_path',
    ];

    protected $casts = [
        'report_date' => 'date',
        'end_date' => 'date',
        'data' => 'array',
    ];

    /**
     * Le projet
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * L'utilisateur qui a créé le rapport
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Obtenir le label du type
     */
    public function getTypeLabelAttribute()
    {
        $types = [
            'journalier' => 'Rapport Journalier',
            'hebdomadaire' => 'Rapport Hebdomadaire',
        ];

        return $types[$this->type] ?? $this->type;
    }
}
