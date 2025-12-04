<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Project;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
    /**
     * Liste des tâches
     */
    public function index(Request $request, $projectId = null)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if (!$companyId && !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez sélectionner une entreprise.',
            ], 400);
        }

        $query = Task::query();

        // Filtrer par projet si fourni
        if ($projectId) {
            $project = Project::find($projectId);
            if (!$project) {
                return response()->json([
                    'success' => false,
                    'message' => 'Projet non trouvé.',
                ], 404);
            }

            if ($project->company_id !== $companyId && !$user->isSuperAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé.',
                ], 403);
            }

            $query->where('project_id', $projectId);
        } else {
            // Filtrer par entreprise
            if ($companyId) {
                $projectIds = Project::forCompany($companyId)->pluck('id');
                $query->whereIn('project_id', $projectIds);
            }
        }

        // Filtre par statut
        if ($request->filled('status')) {
            $query->withStatus($request->status);
        }

        // Filtre par priorité
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        // Filtre par employé assigné
        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        // Recherche
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%')
                  ->orWhere('category', 'like', '%' . $request->search . '%');
            });
        }

        // Tri
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $tasks = $query->with(['project', 'creator', 'assignedEmployee'])->get();

        return response()->json([
            'success' => true,
            'data' => $tasks,
        ], 200);
    }

    /**
     * Détails d'une tâche
     */
    public function show($id, $projectId = null)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if (!$companyId && !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez sélectionner une entreprise.',
            ], 400);
        }

        $task = Task::with(['project', 'creator', 'assignedEmployee'])->find($id);

        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Tâche non trouvée.',
            ], 404);
        }

        // Vérifier l'accès
        if ($task->project->company_id !== $companyId && !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        return response()->json($task, 200);
    }

    /**
     * Créer une tâche
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

        $project = Project::find($projectId);
        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Projet non trouvé.',
            ], 404);
        }

        if ($project->company_id !== $companyId && !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:255',
            'status' => 'required|in:a_faire,en_cours,termine,bloque',
            'priority' => 'required|in:basse,moyenne,haute,urgente',
            'start_date' => 'nullable|date',
            'deadline' => 'nullable|date',
            'progress' => 'nullable|integer|min:0|max:100',
            'assigned_to' => 'nullable|exists:employees,id',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['project_id'] = $projectId;
        $data['created_by'] = $user->id;
        $data['progress'] = $request->input('progress', 0);

        // Vérifier que l'employé appartient à la même entreprise
        if (isset($data['assigned_to']) && $data['assigned_to']) {
            $employee = Employee::find($data['assigned_to']);
            if (!$employee || $employee->company_id !== $companyId) {
                return response()->json([
                    'success' => false,
                    'message' => 'L\'employé sélectionné n\'appartient pas à votre entreprise.',
                ], 422);
            }
        }

        $task = Task::create($data);

        return response()->json($task->load(['project', 'creator', 'assignedEmployee']), 201);
    }

    /**
     * Mettre à jour une tâche
     */
    public function update(Request $request, $id, $projectId = null)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if (!$companyId && !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez sélectionner une entreprise.',
            ], 400);
        }

        $task = Task::find($id);
        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Tâche non trouvée.',
            ], 404);
        }

        if ($task->project->company_id !== $companyId && !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:255',
            'status' => 'sometimes|required|in:a_faire,en_cours,termine,bloque',
            'priority' => 'sometimes|required|in:basse,moyenne,haute,urgente',
            'start_date' => 'nullable|date',
            'deadline' => 'nullable|date',
            'progress' => 'nullable|integer|min:0|max:100',
            'assigned_to' => 'nullable|exists:employees,id',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Vérifier que l'employé appartient à la même entreprise
        if (isset($data['assigned_to']) && $data['assigned_to']) {
            $employee = Employee::find($data['assigned_to']);
            if (!$employee || $employee->company_id !== $companyId) {
                return response()->json([
                    'success' => false,
                    'message' => 'L\'employé sélectionné n\'appartient pas à votre entreprise.',
                ], 422);
            }
        }

        $task->update($data);

        return response()->json($task->load(['project', 'creator', 'assignedEmployee']), 200);
    }

    /**
     * Supprimer une tâche
     */
    public function destroy($id, $projectId = null)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if (!$companyId && !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez sélectionner une entreprise.',
            ], 400);
        }

        $task = Task::find($id);
        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Tâche non trouvée.',
            ], 404);
        }

        if ($task->project->company_id !== $companyId && !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        $task->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tâche supprimée avec succès.',
        ], 200);
    }
}
