<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        
        // Calculer la quantité restante
        if (isset($data['quantity_delivered']) || isset($data['quantity_used'])) {
            $quantityDelivered = $data['quantity_delivered'] ?? $material->pivot->quantity_delivered;
            $quantityUsed = $data['quantity_used'] ?? $material->pivot->quantity_used;
            $data['quantity_remaining'] = max(0, $quantityDelivered - $quantityUsed);
        }

        $project->materials()->updateExistingPivot($materialId, $data);

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
            'message' => 'Matériau mis à jour avec succès.',
            'data' => $formattedData,
        ], 200);
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

        return response()->json([
            'success' => true,
            'message' => 'Matériau retiré du projet avec succès.',
        ], 200);
    }
}

