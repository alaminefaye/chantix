<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ProjectEmployeeController extends Controller
{
    /**
     * Liste des employés d'un projet
     */
    public function index($projectId)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if (!$companyId && !$user->isSuperAdmin()) {
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

        $employees = $project->employees()->wherePivot('is_active', true)->get();

        // Formater les données pour correspondre au modèle Flutter
        $formattedEmployees = $employees->map(function ($employee) {
            return [
                'employee' => $employee,
                'assigned_date' => $employee->pivot->assigned_date 
                    ? \Carbon\Carbon::parse($employee->pivot->assigned_date)->toIso8601String()
                    : null,
                'end_date' => $employee->pivot->end_date 
                    ? \Carbon\Carbon::parse($employee->pivot->end_date)->toIso8601String()
                    : null,
                'notes' => $employee->pivot->notes,
                'is_active' => $employee->pivot->is_active ?? true,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedEmployees,
        ], 200);
    }

    /**
     * Affecter un employé au projet
     */
    public function store(Request $request, $projectId)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if (!$companyId && !$user->isSuperAdmin()) {
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
            'employee_id' => 'required|exists:employees,id',
            'assigned_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $employee = Employee::find($validator->validated()['employee_id']);

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employé non trouvé.',
            ], 404);
        }

        if ($employee->company_id !== $companyId && !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'L\'employé sélectionné n\'appartient pas à votre entreprise.',
            ], 403);
        }

        // Vérifier si l'employé est déjà affecté au projet et actif
        $existingAssignment = $project->employees()->where('employees.id', $employee->id)->first();
        if ($existingAssignment && $existingAssignment->pivot->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Cet employé est déjà affecté à ce projet.',
            ], 422);
        }

        // Si l'employé était déjà affecté mais inactif, le réactiver
        if ($existingAssignment && !$existingAssignment->pivot->is_active) {
            $project->employees()->updateExistingPivot($employee->id, [
                'assigned_date' => $validator->validated()['assigned_date'] ?? now(),
                'notes' => $validator->validated()['notes'] ?? $existingAssignment->pivot->notes,
                'is_active' => true,
                'end_date' => null,
            ]);

            $updatedEmployee = $project->employees()->where('employees.id', $employee->id)->first();

            // Formater les données
            $formattedData = [
                'employee' => $updatedEmployee,
                'assigned_date' => $updatedEmployee->pivot->assigned_date 
                    ? \Carbon\Carbon::parse($updatedEmployee->pivot->assigned_date)->toIso8601String()
                    : null,
                'end_date' => $updatedEmployee->pivot->end_date 
                    ? \Carbon\Carbon::parse($updatedEmployee->pivot->end_date)->toIso8601String()
                    : null,
                'notes' => $updatedEmployee->pivot->notes,
                'is_active' => $updatedEmployee->pivot->is_active ?? true,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Employé réactivé sur le projet avec succès.',
                'data' => $formattedData,
            ], 200);
        }

        $project->employees()->attach($employee->id, [
            'assigned_date' => $validator->validated()['assigned_date'] ?? now(),
            'notes' => $validator->validated()['notes'] ?? null,
            'is_active' => true,
        ]);

        $projectEmployee = $project->employees()->where('employees.id', $employee->id)->first();

        // Formater les données
        $formattedData = [
            'employee' => $projectEmployee,
            'assigned_date' => $projectEmployee->pivot->assigned_date 
                ? \Carbon\Carbon::parse($projectEmployee->pivot->assigned_date)->toIso8601String()
                : null,
            'end_date' => $projectEmployee->pivot->end_date 
                ? \Carbon\Carbon::parse($projectEmployee->pivot->end_date)->toIso8601String()
                : null,
            'notes' => $projectEmployee->pivot->notes,
            'is_active' => $projectEmployee->pivot->is_active ?? true,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Employé affecté au projet avec succès.',
            'data' => $formattedData,
        ], 201);
    }

    /**
     * Retirer un employé du projet
     */
    public function destroy($projectId, $employeeId)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if (!$companyId && !$user->isSuperAdmin()) {
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

        $employee = $project->employees()->where('employees.id', $employeeId)->first();

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employé non trouvé dans ce projet.',
            ], 404);
        }

        // Désactiver l'employé (marquer comme inactif au lieu de supprimer pour garder l'historique)
        $project->employees()->updateExistingPivot($employeeId, [
            'is_active' => false,
            'end_date' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Employé retiré du projet avec succès.',
        ], 200);
    }
}

