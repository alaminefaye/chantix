<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ProjectController extends Controller
{
    /**
     * Liste des projets
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if (!$companyId) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez sélectionner une entreprise.',
            ], 400);
        }

        // Filtrer les projets selon l'accès de l'utilisateur
        // Cette méthode garantit que l'utilisateur ne voit QUE les projets assignés dans project_user
        $query = Project::accessibleByUser($user, $companyId)->with('creator');

        // Filtre par statut
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filtre par responsable
        if ($request->filled('created_by')) {
            $query->where('created_by', $request->created_by);
        }

        // Recherche
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%')
                  ->orWhere('client_name', 'like', '%' . $request->search . '%');
            });
        }

        // Tri
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $projects = $query->get();

        // Vérifier les projets assignés dans project_user
        $assignedProjectIds = \Illuminate\Support\Facades\DB::table('project_user')
            ->where('user_id', $user->id)
            ->pluck('project_id')
            ->toArray();
        
        $role = $user->roleInCompany($companyId);
        $roleName = $role ? $role->name : null;

        // Log pour debug (peut être commenté en production)
        \Log::info('API Projects - Projets retournés pour l\'utilisateur', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'company_id' => $companyId,
            'role_name' => $roleName,
            'projects_count' => $projects->count(),
            'project_ids' => $projects->pluck('id')->toArray(),
            'assigned_project_ids' => $assignedProjectIds,
            'assigned_count' => count($assignedProjectIds),
            'is_admin' => $user->isSuperAdmin() || $user->hasRoleInCompany('admin', $companyId),
            'is_super_admin' => $user->isSuperAdmin(),
            'has_role_admin' => $user->hasRoleInCompany('admin', $companyId)
        ]);

        return response()->json([
            'success' => true,
            'data' => $projects,
        ], 200);
    }

    /**
     * Détails d'un projet
     */
    public function show($id)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if (!$companyId) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez sélectionner une entreprise.',
            ], 400);
        }

        // Récupérer le projet avec vérification d'accès
        $project = Project::forCompany($companyId)
            ->with([
                'creator', 
                'company',
                'materials',
                'employees',
                'tasks' => function($query) {
                    $query->orderBy('created_at', 'desc')->limit(10);
                },
            ])
            ->find($id);

        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Projet non trouvé.',
            ], 404);
        }

        // Vérifier l'accès au projet spécifique
        if (!$user->isSuperAdmin() && !$user->hasRoleInCompany('admin', $project->company_id)) {
            $hasAccess = $project->users()->where('users.id', $user->id)->exists() 
                      || $project->created_by == $user->id;
            
            if (!$hasAccess) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'avez pas accès à ce projet.',
                ], 403);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $project,
        ], 200);
    }

    /**
     * Créer un projet
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if (!$companyId) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez sélectionner une entreprise.',
            ], 400);
        }

        // Vérifier les permissions : seuls admin et chef_chantier peuvent créer des projets
        if (!$user->isSuperAdmin() && !$user->hasRoleInCompany('admin', $companyId) && !$user->hasPermission('projects.create', $companyId)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas la permission de créer des projets.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'budget' => 'required|numeric|min:0',
            'client_name' => 'nullable|string|max:255',
            'client_contact' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Les données fournies sont invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $project = Project::create([
            'name' => $request->name,
            'description' => $request->description,
            'address' => $request->address,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'budget' => $request->budget,
            'client_name' => $request->client_name,
            'client_contact' => $request->client_contact,
            'status' => 'non_demarre',
            'progress' => 0,
            'company_id' => $companyId,
            'created_by' => $user->id,
        ]);

        // Associer automatiquement le créateur au projet
        if (!$project->users()->where('users.id', $user->id)->exists()) {
            $project->users()->attach($user->id);
        }

        return response()->json([
            'success' => true,
            'message' => 'Projet créé avec succès.',
            'data' => $project->load('creator'),
        ], 201);
    }

    /**
     * Mettre à jour un projet
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if (!$companyId) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez sélectionner une entreprise.',
            ], 400);
        }

        $project = Project::forCompany($companyId)->find($id);

        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Projet non trouvé.',
            ], 404);
        }

        // Vérifier les permissions : seuls admin et chef_chantier peuvent modifier des projets
        if (!$user->isSuperAdmin() && !$user->hasRoleInCompany('admin', $companyId) && !$user->hasPermission('projects.update', $companyId)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas la permission de modifier ce projet.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'budget' => 'sometimes|required|numeric|min:0',
            'status' => 'sometimes|in:non_demarre,en_cours,termine,bloque',
            'progress' => 'sometimes|integer|min:0|max:100',
            'client_name' => 'nullable|string|max:255',
            'client_contact' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Les données fournies sont invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Sauvegarder les anciennes valeurs pour détecter les changements importants
        $oldStatus = $project->status;
        $oldProgress = $project->progress;
        
        $project->update($request->only([
            'name', 'description', 'address', 'latitude', 'longitude',
            'start_date', 'end_date', 'budget', 'status', 'progress',
            'client_name', 'client_contact',
        ]));

        // Recharger le projet avec les relations
        $project->refresh();
        $project->load('creator');

        // Envoyer des notifications push aux utilisateurs concernés (en arrière-plan pour ne pas bloquer)
        try {
            \Log::info("📝 Starting notification process for project update", [
                'project_id' => $project->id,
                'user_id' => $user->id,
            ]);

            $pushService = new PushNotificationService();
            
            // Déterminer le message selon ce qui a changé
            $message = "Le projet \"{$project->name}\" a été mis à jour";
            $title = "Projet mis à jour";
            
            // Si le statut a changé, mentionner le nouveau statut
            if ($oldStatus !== $project->status) {
                $statusLabels = [
                    'non_demarre' => 'Non démarré',
                    'en_cours' => 'En cours',
                    'termine' => 'Terminé',
                    'bloque' => 'Bloqué',
                ];
                $newStatusLabel = $statusLabels[$project->status] ?? $project->status;
                $message = "Le statut du projet \"{$project->name}\" a été changé à : {$newStatusLabel}";
                $title = "Statut du projet modifié";
            } elseif ($oldProgress !== $project->progress) {
                // Si l'avancement a changé
                $message = "L'avancement du projet \"{$project->name}\" a été mis à jour : {$project->progress}%";
                $title = "Avancement du projet mis à jour";
            }
            
            // Recharger le projet pour s'assurer d'avoir toutes les données
            $project->refresh();
            
            $notifications = $pushService->notifyProjectStakeholders(
                $project,
                'project_updated',
                $title,
                $message,
                [
                    'project_id' => $project->id,
                    'project_name' => $project->name,
                    'status' => $project->status,
                    'progress' => $project->progress,
                ],
                $user->id // Exclure l'utilisateur qui a mis à jour le projet
            );

            \Log::info("✅ Notification process completed for project update", [
                'notifications_created' => count($notifications),
            ]);
        } catch (\Exception $e) {
            // Ne pas faire échouer la mise à jour du projet si l'envoi de notification échoue
            \Log::error("❌ Failed to send project update notification: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'project_id' => $project->id ?? null,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Projet mis à jour avec succès.',
            'data' => $project,
        ], 200);
    }

    /**
     * Supprimer un projet
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if (!$companyId) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez sélectionner une entreprise.',
            ], 400);
        }

        $project = Project::forCompany($companyId)->find($id);

        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Projet non trouvé.',
            ], 404);
        }

        $project->delete();

        return response()->json([
            'success' => true,
            'message' => 'Projet supprimé avec succès.',
        ], 200);
    }
}

