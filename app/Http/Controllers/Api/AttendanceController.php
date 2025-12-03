<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Project;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * Liste des pointages pour un projet
     */
    public function index(Request $request, $projectId)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if (!$companyId) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez sélectionner une entreprise.',
            ], 400);
        }

        $project = Project::forCompany($companyId)->find($projectId);

        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Projet non trouvé.',
            ], 404);
        }

        $query = Attendance::where('project_id', $projectId)
            ->with('employee')
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc');

        // Filtre par date
        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        // Filtre par employé
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        $attendances = $query->get();

        // Formater les données pour correspondre au modèle Flutter
        $formattedAttendances = $attendances->map(function ($attendance) {
            return [
                'id' => $attendance->id,
                'project_id' => $attendance->project_id,
                'employee_id' => $attendance->employee_id,
                'check_in_time' => $attendance->check_in 
                    ? Carbon::parse($attendance->date->format('Y-m-d') . ' ' . $attendance->check_in)->toIso8601String()
                    : null,
                'check_out_time' => $attendance->check_out 
                    ? Carbon::parse($attendance->date->format('Y-m-d') . ' ' . $attendance->check_out)->toIso8601String()
                    : null,
                'check_in_latitude' => $this->extractLatitude($attendance->check_in_location),
                'check_in_longitude' => $this->extractLongitude($attendance->check_in_location),
                'check_out_latitude' => $this->extractLatitude($attendance->check_out_location),
                'check_out_longitude' => $this->extractLongitude($attendance->check_out_location),
                'check_in_photo' => $attendance->check_in_photo 
                    ? Storage::url($attendance->check_in_photo) 
                    : null,
                'check_out_photo' => $attendance->check_out_photo 
                    ? Storage::url($attendance->check_out_photo) 
                    : null,
                'hours_worked' => $attendance->hours_worked ? (float)$attendance->hours_worked : null,
                'overtime_hours' => $attendance->overtime_hours ? (float)$attendance->overtime_hours : null,
                'notes' => $attendance->notes,
                'is_absence' => !$attendance->is_present,
                'absence_reason' => $attendance->absence_reason,
                'created_at' => $attendance->created_at?->toIso8601String(),
                'updated_at' => $attendance->updated_at?->toIso8601String(),
                'employee' => $attendance->employee ? [
                    'id' => $attendance->employee->id,
                    'first_name' => $attendance->employee->first_name,
                    'last_name' => $attendance->employee->last_name,
                    'email' => $attendance->employee->email,
                ] : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedAttendances,
        ], 200);
    }

    /**
     * Check-in
     */
    public function checkIn(Request $request, $projectId)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if (!$companyId) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez sélectionner une entreprise.',
            ], 400);
        }

        $project = Project::forCompany($companyId)->find($projectId);

        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Projet non trouvé.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'check_in_latitude' => 'nullable|numeric',
            'check_in_longitude' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Les données fournies sont invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Utiliser l'ID de l'utilisateur connecté comme employee_id
        // TODO: Créer une relation User-Employee si nécessaire
        $employeeId = $user->id;
        $date = Carbon::today();

        // Vérifier si un pointage existe déjà pour aujourd'hui
        $existing = Attendance::where('project_id', $projectId)
            ->where('employee_id', $employeeId)
            ->whereDate('date', $date)
            ->first();

        if ($existing && $existing->check_in) {
            return response()->json([
                'success' => false,
                'message' => 'Un check-in existe déjà pour aujourd\'hui.',
            ], 400);
        }

        // Créer ou mettre à jour le pointage
        if ($existing) {
            $attendance = $existing;
        } else {
            $attendance = new Attendance();
            $attendance->project_id = $projectId;
            $attendance->employee_id = $employeeId;
            $attendance->date = $date;
        }

        $attendance->check_in = Carbon::now()->format('H:i');
        $attendance->check_in_location = $request->check_in_latitude && $request->check_in_longitude
            ? $request->check_in_latitude . ',' . $request->check_in_longitude
            : null;
        $attendance->is_present = true;
        $attendance->save();

        // Formater la réponse
        $formattedAttendance = [
            'id' => $attendance->id,
            'project_id' => $attendance->project_id,
            'employee_id' => $attendance->employee_id,
            'check_in_time' => Carbon::parse($attendance->date->format('Y-m-d') . ' ' . $attendance->check_in)->toIso8601String(),
            'check_in_latitude' => $this->extractLatitude($attendance->check_in_location),
            'check_in_longitude' => $this->extractLongitude($attendance->check_in_location),
            'is_absence' => false,
            'created_at' => $attendance->created_at->toIso8601String(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Check-in effectué avec succès.',
            'data' => $formattedAttendance,
        ], 201);
    }

    /**
     * Check-out
     */
    public function checkOut(Request $request, $projectId, $attendanceId)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if (!$companyId) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez sélectionner une entreprise.',
            ], 400);
        }

        $project = Project::forCompany($companyId)->find($projectId);

        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Projet non trouvé.',
            ], 404);
        }

        $attendance = Attendance::where('project_id', $projectId)
            ->where('id', $attendanceId)
            ->first();

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'message' => 'Pointage non trouvé.',
            ], 404);
        }

        if ($attendance->check_out) {
            return response()->json([
                'success' => false,
                'message' => 'Le check-out a déjà été effectué.',
            ], 400);
        }

        $attendance->check_out = Carbon::now()->format('H:i');
        $attendance->check_out_location = $request->check_out_latitude && $request->check_out_longitude
            ? $request->check_out_latitude . ',' . $request->check_out_longitude
            : null;
        $attendance->calculateHoursWorked();
        $attendance->save();

        // Formater la réponse
        $formattedAttendance = [
            'id' => $attendance->id,
            'project_id' => $attendance->project_id,
            'employee_id' => $attendance->employee_id,
            'check_in_time' => $attendance->check_in 
                ? Carbon::parse($attendance->date->format('Y-m-d') . ' ' . $attendance->check_in)->toIso8601String()
                : null,
            'check_out_time' => Carbon::parse($attendance->date->format('Y-m-d') . ' ' . $attendance->check_out)->toIso8601String(),
            'check_out_latitude' => $this->extractLatitude($attendance->check_out_location),
            'check_out_longitude' => $this->extractLongitude($attendance->check_out_location),
            'hours_worked' => $attendance->hours_worked ? (float)$attendance->hours_worked : null,
            'overtime_hours' => $attendance->overtime_hours ? (float)$attendance->overtime_hours : null,
            'is_absence' => false,
            'updated_at' => $attendance->updated_at->toIso8601String(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Check-out effectué avec succès.',
            'data' => $formattedAttendance,
        ], 200);
    }

    /**
     * Déclarer une absence
     */
    public function absence(Request $request, $projectId)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if (!$companyId) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez sélectionner une entreprise.',
            ], 400);
        }

        $project = Project::forCompany($companyId)->find($projectId);

        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Projet non trouvé.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'absence_reason' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Les données fournies sont invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Utiliser l'ID de l'utilisateur connecté comme employee_id
        // TODO: Créer une relation User-Employee si nécessaire
        $employeeId = $user->id;
        $date = Carbon::today();

        // Vérifier si un pointage existe déjà pour aujourd'hui
        $existing = Attendance::where('project_id', $projectId)
            ->where('employee_id', $employeeId)
            ->whereDate('date', $date)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Un pointage existe déjà pour aujourd\'hui.',
            ], 400);
        }

        $attendance = Attendance::create([
            'project_id' => $projectId,
            'employee_id' => $employeeId,
            'date' => $date,
            'is_present' => false,
            'absence_reason' => $request->absence_reason,
        ]);

        // Formater la réponse
        $formattedAttendance = [
            'id' => $attendance->id,
            'project_id' => $attendance->project_id,
            'employee_id' => $attendance->employee_id,
            'is_absence' => true,
            'absence_reason' => $attendance->absence_reason,
            'created_at' => $attendance->created_at->toIso8601String(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Absence déclarée avec succès.',
            'data' => $formattedAttendance,
        ], 201);
    }

    /**
     * Extraire la latitude d'une chaîne de localisation
     */
    private function extractLatitude(?string $location): ?float
    {
        if (!$location) {
            return null;
        }
        
        $parts = explode(',', $location);
        return isset($parts[0]) ? (float)trim($parts[0]) : null;
    }

    /**
     * Extraire la longitude d'une chaîne de localisation
     */
    private function extractLongitude(?string $location): ?float
    {
        if (!$location) {
            return null;
        }
        
        $parts = explode(',', $location);
        return isset($parts[1]) ? (float)trim($parts[1]) : null;
    }
}

