<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MaterialController extends Controller
{
    /**
     * Liste des matériaux
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if (!$companyId && !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez sélectionner une entreprise.',
            ], 400);
        }

        $query = $companyId 
            ? Material::forCompany($companyId)
            : Material::query();

        // Filtre par statut actif
        if ($request->filled('active')) {
            $query->active();
        }

        // Recherche
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%')
                  ->orWhere('category', 'like', '%' . $request->search . '%')
                  ->orWhere('reference', 'like', '%' . $request->search . '%');
            });
        }

        // Tri
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $materials = $query->get();

        return response()->json([
            'success' => true,
            'data' => $materials,
        ], 200);
    }

    /**
     * Détails d'un matériau
     */
    public function show($id)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if (!$companyId && !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez sélectionner une entreprise.',
            ], 400);
        }

        $material = $companyId
            ? Material::forCompany($companyId)->find($id)
            : Material::find($id);

        if (!$material) {
            return response()->json([
                'success' => false,
                'message' => 'Matériau non trouvé.',
            ], 404);
        }

        return response()->json($material, 200);
    }

    /**
     * Créer un matériau
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if (!$companyId && !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez sélectionner une entreprise.',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:255',
            'unit' => 'nullable|string|max:50',
            'unit_price' => 'nullable|numeric|min:0',
            'supplier' => 'nullable|string|max:255',
            'reference' => 'nullable|string|max:255',
            'stock_quantity' => 'nullable|numeric|min:0',
            'min_stock' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['company_id'] = $companyId;
        $data['is_active'] = $request->input('is_active', true);

        $material = Material::create($data);

        // Créer et envoyer une notification push aux utilisateurs de l'entreprise
        try {
            $pushService = new PushNotificationService();
            $pushService->createAndSendToCompany(
                $companyId,
                'material_created',
                'Nouveau matériau ajouté',
                "Le matériau {$material->name} a été ajouté au stock.",
                null,
                [
                    'material_id' => $material->id,
                    'material_name' => $material->name,
                ]
            );
        } catch (\Exception $e) {
            // Log l'erreur mais ne fait pas échouer la création du matériau
            \Log::warning('Erreur lors de l\'envoi de la notification push pour le matériau: ' . $e->getMessage());
        }

        return response()->json($material, 201);
    }

    /**
     * Mettre à jour un matériau
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if (!$companyId && !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez sélectionner une entreprise.',
            ], 400);
        }

        $material = $companyId
            ? Material::forCompany($companyId)->find($id)
            : Material::find($id);

        if (!$material) {
            return response()->json([
                'success' => false,
                'message' => 'Matériau non trouvé.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:255',
            'unit' => 'nullable|string|max:50',
            'unit_price' => 'nullable|numeric|min:0',
            'supplier' => 'nullable|string|max:255',
            'reference' => 'nullable|string|max:255',
            'stock_quantity' => 'nullable|numeric|min:0',
            'min_stock' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Sauvegarder les anciennes valeurs pour détecter les changements de stock
        $oldStockQuantity = $material->stock_quantity;
        $oldMinStock = $material->min_stock;
        
        $material->update($validator->validated());

        // Envoyer des notifications push pour les mises à jour de stock
        try {
            $pushService = new PushNotificationService();
            
            // Vérifier si le stock a changé
            if ($request->has('stock_quantity') && $request->stock_quantity != $oldStockQuantity) {
                $newStockQuantity = $request->stock_quantity;
                $stockChange = $newStockQuantity - $oldStockQuantity;
                
                if ($stockChange > 0) {
                    // Stock augmenté
                    $pushService->createAndSendToCompany(
                        $companyId,
                        'material_stock_increased',
                        'Stock mis à jour',
                        "Le stock de {$material->name} a été augmenté de {$stockChange} {$material->unit}. Nouveau stock: {$newStockQuantity} {$material->unit}.",
                        null,
                        [
                            'material_id' => $material->id,
                            'material_name' => $material->name,
                            'old_stock' => $oldStockQuantity,
                            'new_stock' => $newStockQuantity,
                            'change' => $stockChange,
                        ]
                    );
                } else {
                    // Stock diminué
                    $pushService->createAndSendToCompany(
                        $companyId,
                        'material_stock_decreased',
                        'Stock mis à jour',
                        "Le stock de {$material->name} a été réduit de " . abs($stockChange) . " {$material->unit}. Nouveau stock: {$newStockQuantity} {$material->unit}.",
                        null,
                        [
                            'material_id' => $material->id,
                            'material_name' => $material->name,
                            'old_stock' => $oldStockQuantity,
                            'new_stock' => $newStockQuantity,
                            'change' => $stockChange,
                        ]
                    );
                }
                
                // Vérifier si le stock est faible
                if ($newStockQuantity <= $material->min_stock) {
                    $pushService->createAndSendToCompany(
                        $companyId,
                        'material_low_stock',
                        '⚠️ Stock faible',
                        "Attention: Le stock de {$material->name} est faible ({$newStockQuantity} {$material->unit}). Stock minimum: {$material->min_stock} {$material->unit}.",
                        null,
                        [
                            'material_id' => $material->id,
                            'material_name' => $material->name,
                            'current_stock' => $newStockQuantity,
                            'min_stock' => $material->min_stock,
                        ]
                    );
                }
            } else {
                // Autre mise à jour (nom, description, etc.)
                $pushService->createAndSendToCompany(
                    $companyId,
                    'material_updated',
                    'Matériau mis à jour',
                    "Le matériau {$material->name} a été modifié.",
                    null,
                    [
                        'material_id' => $material->id,
                        'material_name' => $material->name,
                    ]
                );
            }
        } catch (\Exception $e) {
            // Log l'erreur mais ne fait pas échouer la mise à jour du matériau
            \Log::warning('Erreur lors de l\'envoi de la notification push pour le matériau: ' . $e->getMessage());
        }

        return response()->json($material, 200);
    }

    /**
     * Supprimer un matériau
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if (!$companyId && !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez sélectionner une entreprise.',
            ], 400);
        }

        $material = $companyId
            ? Material::forCompany($companyId)->find($id)
            : Material::find($id);

        if (!$material) {
            return response()->json([
                'success' => false,
                'message' => 'Matériau non trouvé.',
            ], 404);
        }

        $materialName = $material->name;
        $materialCompanyId = $material->company_id;
        
        $material->delete();

        // Créer et envoyer une notification push
        try {
            $pushService = new PushNotificationService();
            $pushService->createAndSendToCompany(
                $materialCompanyId,
                'material_deleted',
                'Matériau supprimé',
                "Le matériau {$materialName} a été supprimé du stock.",
                null,
                [
                    'material_name' => $materialName,
                ]
            );
        } catch (\Exception $e) {
            // Log l'erreur mais ne fait pas échouer la suppression du matériau
            \Log::warning('Erreur lors de l\'envoi de la notification push pour le matériau: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Matériau supprimé avec succès.',
        ], 200);
    }
}

