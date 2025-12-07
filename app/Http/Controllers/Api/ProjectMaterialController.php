<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Material;
use App\Models\ProjectMaterial;
use App\Services\StockService;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProjectMaterialController extends Controller
{
    /**
     * Liste des matériaux d'un projet
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

        $materials = $project->materials()->get();

        // Formater les données pour correspondre au modèle Flutter
        $formattedMaterials = $materials->map(function ($material) {
            return [
                'material' => $material,
                'quantity_planned' => $material->pivot->quantity_planned ?? 0,
                'quantity_ordered' => $material->pivot->quantity_ordered ?? 0,
                'quantity_delivered' => $material->pivot->quantity_delivered ?? 0,
                'quantity_used' => $material->pivot->quantity_used ?? 0,
                'quantity_remaining' => $material->pivot->quantity_remaining ?? 0,
                'notes' => $material->pivot->notes,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedMaterials,
        ], 200);
    }

    /**
     * Ajouter un matériau au projet
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
            'material_id' => 'required|exists:materials,id',
            'quantity_planned' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $material = Material::find($validator->validated()['material_id']);

        if ($material->company_id !== $companyId && !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Le matériau sélectionné n\'appartient pas à votre entreprise.',
            ], 403);
        }

        // Vérifier si le matériau est déjà associé au projet
        if ($project->materials()->where('materials.id', $material->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Ce matériau est déjà associé à ce projet.',
            ], 422);
        }

        $project->materials()->attach($material->id, [
            'quantity_planned' => $validator->validated()['quantity_planned'],
            'quantity_remaining' => $validator->validated()['quantity_planned'],
            'notes' => $validator->validated()['notes'] ?? null,
        ]);

        $projectMaterial = $project->materials()->where('materials.id', $material->id)->first();

        // Envoyer des notifications push aux utilisateurs du projet
        try {
            $pushService = new PushNotificationService();
            $pushService->notifyProjectStakeholders(
                $project,
                'material_added',
                'Nouveau matériau ajouté',
                $user->name . ' a ajouté le matériau "' . $material->name . '" au projet "' . $project->name . '"',
                [
                    'material_id' => $material->id,
                    'material_name' => $material->name,
                    'quantity_planned' => $validator->validated()['quantity_planned'],
                ],
                $user->id
            );
        } catch (\Exception $e) {
            \Log::warning("Failed to send material added push notification: " . $e->getMessage());
        }

        // Formater les données
        $formattedData = [
            'material' => $projectMaterial,
            'quantity_planned' => $projectMaterial->pivot->quantity_planned ?? 0,
            'quantity_ordered' => $projectMaterial->pivot->quantity_ordered ?? 0,
            'quantity_delivered' => $projectMaterial->pivot->quantity_delivered ?? 0,
            'quantity_used' => $projectMaterial->pivot->quantity_used ?? 0,
            'quantity_remaining' => $projectMaterial->pivot->quantity_remaining ?? 0,
            'notes' => $projectMaterial->pivot->notes,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Matériau ajouté au projet avec succès.',
            'data' => $formattedData,
        ], 201);
    }

    /**
     * Mettre à jour un matériau du projet
     */
    public function update(Request $request, $projectId, $materialId)
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

        $material = $project->materials()->where('materials.id', $materialId)->first();

        if (!$material) {
            return response()->json([
                'success' => false,
                'message' => 'Matériau non trouvé dans ce projet.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'quantity_ordered' => 'nullable|numeric|min:0',
            'quantity_delivered' => 'nullable|numeric|min:0',
            'quantity_used' => 'nullable|numeric|min:0',
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
        
        // Récupérer le ProjectMaterial pour gérer les stocks
        $projectMaterial = ProjectMaterial::where('project_id', $projectId)
            ->where('material_id', $materialId)
            ->firstOrFail();

        $stockService = new StockService();

        DB::beginTransaction();
        try {
            // Sauvegarder les anciennes valeurs AVANT la mise à jour
            $oldDelivered = $projectMaterial->quantity_delivered;
            $oldUsed = $projectMaterial->quantity_used;

            // Calculer la quantité restante
            if (isset($data['quantity_delivered']) || isset($data['quantity_used'])) {
                $quantityDelivered = $data['quantity_delivered'] ?? $projectMaterial->quantity_delivered;
                $quantityUsed = $data['quantity_used'] ?? $projectMaterial->quantity_used;
                $data['quantity_remaining'] = max(0, $quantityDelivered - $quantityUsed);
            }

            // Mettre à jour les quantités dans la table pivot
            $project->materials()->updateExistingPivot($materialId, $data);

            // Recharger le ProjectMaterial pour avoir les nouvelles valeurs
            $projectMaterial->refresh();

            // Mettre à jour le stock si les quantités ont changé
            if (isset($data['quantity_delivered']) && $data['quantity_delivered'] != $oldDelivered) {
                $stockService->handleDelivery($projectMaterial, $data['quantity_delivered'], $oldDelivered);
            }

            if (isset($data['quantity_used']) && $data['quantity_used'] != $oldUsed) {
                $stockService->handleUsage($projectMaterial, $data['quantity_used'], $oldUsed);
            }

            DB::commit();

            $updatedMaterial = $project->materials()->where('materials.id', $materialId)->first();

        // Formater les données
        $formattedData = [
            'material' => $updatedMaterial,
            'quantity_planned' => $updatedMaterial->pivot->quantity_planned ?? 0,
            'quantity_ordered' => $updatedMaterial->pivot->quantity_ordered ?? 0,
            'quantity_delivered' => $updatedMaterial->pivot->quantity_delivered ?? 0,
            'quantity_used' => $updatedMaterial->pivot->quantity_used ?? 0,
            'quantity_remaining' => $updatedMaterial->pivot->quantity_remaining ?? 0,
            'notes' => $updatedMaterial->pivot->notes,
        ];

            return response()->json([
                'success' => true,
                'message' => 'Matériau mis à jour avec succès. Le stock a été mis à jour automatiquement.',
                'data' => $formattedData,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer un matériau du projet
     */
    public function destroy($projectId, $materialId)
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

        $material = $project->materials()->where('materials.id', $materialId)->first();

        if (!$material) {
            return response()->json([
                'success' => false,
                'message' => 'Matériau non trouvé dans ce projet.',
            ], 404);
        }

        $project->materials()->detach($materialId);

        // Envoyer des notifications push aux utilisateurs du projet
        try {
            $pushService = new PushNotificationService();
            $pushService->notifyProjectStakeholders(
                $project,
                'material_removed',
                'Matériau retiré',
                $user->name . ' a retiré le matériau "' . $material->name . '" du projet "' . $project->name . '"',
                [
                    'material_id' => $material->id,
                    'material_name' => $material->name,
                ],
                $user->id
            );
        } catch (\Exception $e) {
            \Log::warning("Failed to send material removed push notification: " . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Matériau retiré du projet avec succès.',
        ], 200);
    }
}

