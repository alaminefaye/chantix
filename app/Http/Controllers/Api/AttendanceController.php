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

        // Vérifier que le projet existe et que l'utilisateur y a accès
        $project = Project::accessibleByUser($user, $companyId)->find($projectId);

        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Projet non trouvé ou accès refusé.',
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
        \Log::info('Check-in appelé', [
            'project_id' => $projectId,
            'user_id' => Auth::id(),
            'request_data' => $request->except(['check_in_photo']), // Exclure le fichier pour le log
        ]);
        
        $user = Auth::user();
        $companyId = $user->current_company_id;

        \Log::info('Check-in - Vérification entreprise', [
            'company_id' => $companyId,
            'user_id' => $user->id,
        ]);

        if (!$companyId) {
            \Log::warning('Check-in - Aucune entreprise sélectionnée', ['user_id' => $user->id]);
            return response()->json([
                'success' => false,
                'message' => 'Veuillez sélectionner une entreprise.',
            ], 400);
        }

        // Vérifier que le projet existe et que l'utilisateur y a accès
        $project = Project::accessibleByUser($user, $companyId)->find($projectId);

        \Log::info('Check-in - Vérification projet et accès', [
            'project_id' => $projectId,
            'company_id' => $companyId,
            'project_found' => $project ? 'yes' : 'no',
            'user_id' => $user->id,
        ]);

        if (!$project) {
            \Log::warning('Check-in - Projet non trouvé ou accès refusé', [
                'project_id' => $projectId,
                'company_id' => $companyId,
                'user_id' => $user->id,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Projet non trouvé ou accès refusé.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'check_in_latitude' => 'nullable|numeric',
            'check_in_longitude' => 'nullable|numeric',
            'check_in_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            \Log::warning('Check-in - Validation échouée', ['errors' => $validator->errors()->toArray()]);
            return response()->json([
                'success' => false,
                'message' => 'Les données fournies sont invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Trouver l'employé associé à l'utilisateur par email
        $employee = Employee::where('company_id', $companyId)
            ->where('email', $user->email)
            ->first();

        \Log::info('Check-in - Vérification employé', [
            'user_email' => $user->email,
            'company_id' => $companyId,
            'employee_found' => $employee ? 'yes' : 'no',
            'employee_id' => $employee?->id,
        ]);

        // Si l'employé n'existe pas, le créer automatiquement
        if (!$employee) {
            \Log::info('Check-in - Création automatique de l\'employé', [
                'user_email' => $user->email,
                'company_id' => $companyId,
                'user_name' => $user->name,
            ]);

            // Extraire le prénom et nom depuis le nom complet de l'utilisateur
            $nameParts = explode(' ', $user->name, 2);
            $firstName = $nameParts[0] ?? $user->name;
            $lastName = $nameParts[1] ?? '';

            $employee = Employee::create([
                'company_id' => $companyId,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $user->email,
                'is_active' => true,
                'hire_date' => now(),
            ]);

            \Log::info('Check-in - Employé créé', [
                'employee_id' => $employee->id,
                'email' => $employee->email,
            ]);
        }

        $employeeId = $employee->id;
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

        // Upload de la photo de check-in si fournie
        $checkInPhoto = null;
        if ($request->hasFile('check_in_photo')) {
            $checkInPhoto = $request->file('check_in_photo')->store('attendances/check-in', 'public');
        }

        $attendance->check_in = Carbon::now()->format('H:i');
        $attendance->check_in_location = $request->check_in_latitude && $request->check_in_longitude
            ? $request->check_in_latitude . ',' . $request->check_in_longitude
            : null;
        $attendance->check_in_photo = $checkInPhoto;
        $attendance->is_present = true;
        $attendance->save();

        \Log::info('Check-in - Pointage sauvegardé', [
            'attendance_id' => $attendance->id,
            'check_in_time' => $attendance->check_in,
        ]);

        // Formater la réponse
        $formattedAttendance = [
            'id' => $attendance->id,
            'project_id' => $attendance->project_id,
            'employee_id' => $attendance->employee_id,
            'check_in_time' => Carbon::parse($attendance->date->format('Y-m-d') . ' ' . $attendance->check_in)->toIso8601String(),
            'check_in_latitude' => $this->extractLatitude($attendance->check_in_location),
            'check_in_longitude' => $this->extractLongitude($attendance->check_in_location),
            'check_in_photo' => $attendance->check_in_photo 
                ? Storage::url($attendance->check_in_photo) 
                : null,
            'is_absence' => false,
            'created_at' => $attendance->created_at->toIso8601String(),
        ];

        \Log::info('Check-in - Réponse envoyée', [
            'attendance_id' => $attendance->id,
            'status_code' => 201,
        ]);

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

        // Vérifier que le projet existe et que l'utilisateur y a accès
        $project = Project::accessibleByUser($user, $companyId)->find($projectId);

        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Projet non trouvé ou accès refusé.',
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

        $validator = Validator::make($request->all(), [
            'check_out_latitude' => 'nullable|numeric',
            'check_out_longitude' => 'nullable|numeric',
            'check_out_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Les données fournies sont invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Upload de la photo de check-out si fournie
        $checkOutPhoto = null;
        if ($request->hasFile('check_out_photo')) {
            $checkOutPhoto = $request->file('check_out_photo')->store('attendances/check-out', 'public');
        }

        $attendance->check_out = Carbon::now()->format('H:i');
        $attendance->check_out_location = $request->check_out_latitude && $request->check_out_longitude
            ? $request->check_out_latitude . ',' . $request->check_out_longitude
            : null;
        $attendance->check_out_photo = $checkOutPhoto;
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
            'check_out_photo' => $attendance->check_out_photo 
                ? Storage::url($attendance->check_out_photo) 
                : null,
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

        // Vérifier que le projet existe et que l'utilisateur y a accès
        $project = Project::accessibleByUser($user, $companyId)->find($projectId);

        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Projet non trouvé ou accès refusé.',
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

        // Trouver l'employé associé à l'utilisateur par email
        $employee = Employee::where('company_id', $companyId)
            ->where('email', $user->email)
            ->first();

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun employé trouvé associé à votre compte. Veuillez contacter l\'administrateur.',
            ], 404);
        }

        $employeeId = $employee->id;
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

