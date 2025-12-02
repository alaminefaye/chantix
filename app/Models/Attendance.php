<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Attendance extends Model
{
    protected $fillable = [
        'project_id',
        'employee_id',
        'date',
        'check_in',
        'check_out',
        'hours_worked',
        'overtime_hours',
        'check_in_location',
        'check_out_location',
        'notes',
        'is_present',
        'absence_reason',
    ];

    protected $casts = [
        'date' => 'date',
        'hours_worked' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'is_present' => 'boolean',
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

    /**
     * Calculer les heures travaillées automatiquement
     */
    public function calculateHoursWorked()
    {
        if ($this->check_in && $this->check_out) {
            // Parser les heures (format H:i)
            $checkInParts = explode(':', $this->check_in);
            $checkOutParts = explode(':', $this->check_out);
            
            $checkInMinutes = (int)$checkInParts[0] * 60 + (int)$checkInParts[1];
            $checkOutMinutes = (int)$checkOutParts[0] * 60 + (int)$checkOutParts[1];
            
            // Si check-out est avant check-in, on suppose que c'est le lendemain
            if ($checkOutMinutes < $checkInMinutes) {
                $checkOutMinutes += 24 * 60;
            }
            
            $totalMinutes = $checkOutMinutes - $checkInMinutes;
            $hours = $totalMinutes / 60;
            
            // Calculer les heures supplémentaires (au-delà de 8 heures)
            $this->overtime_hours = max(0, $hours - 8);
            $this->hours_worked = round($hours, 2);
        }
    }

    /**
     * Scope pour une date spécifique
     */
    public function scopeForDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    /**
     * Scope pour un projet
     */
    public function scopeForProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Scope pour un employé
     */
    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Scope pour les présences
     */
    public function scopePresent($query)
    {
        return $query->where('is_present', true);
    }
}
