<?php

namespace App\Services;

use App\Models\Material;
use App\Models\Project;
use App\Models\StockMovement;
use App\Models\ProjectMaterial;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Enregistrer une entrée de stock (livraison)
     */
    public function addStock(Material $material, float $quantity, ?Project $project = null, ?string $reason = null, ?string $notes = null, ?string $reference = null): StockMovement
    {
        return DB::transaction(function () use ($material, $quantity, $project, $reason, $notes, $reference) {
            $stockBefore = $material->stock_quantity;
            $stockAfter = $stockBefore + $quantity;

            // Mettre à jour le stock du matériau
            $material->stock_quantity = $stockAfter;
            $material->save();

            // Enregistrer le mouvement
            $movement = StockMovement::create([
                'material_id' => $material->id,
                'project_id' => $project?->id,
                'user_id' => Auth::id(),
                'type' => 'in',
                'quantity' => $quantity,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'reason' => $reason ?? 'Livraison',
                'notes' => $notes,
                'reference' => $reference,
            ]);

            return $movement;
        });
    }

    /**
     * Enregistrer une sortie de stock (utilisation)
     */
    public function removeStock(Material $material, float $quantity, ?Project $project = null, ?string $reason = null, ?string $notes = null): StockMovement
    {
        return DB::transaction(function () use ($material, $quantity, $project, $reason, $notes) {
            $stockBefore = $material->stock_quantity;
            
            // Vérifier que le stock est suffisant
            if ($stockBefore < $quantity) {
                throw new \Exception("Stock insuffisant. Stock disponible: {$stockBefore} {$material->unit}, Quantité demandée: {$quantity} {$material->unit}");
            }

            $stockAfter = $stockBefore - $quantity;

            // Mettre à jour le stock du matériau
            $material->stock_quantity = $stockAfter;
            $material->save();

            // Enregistrer le mouvement
            $movement = StockMovement::create([
                'material_id' => $material->id,
                'project_id' => $project?->id,
                'user_id' => Auth::id(),
                'type' => 'out',
                'quantity' => -$quantity, // Négatif pour sortie
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'reason' => $reason ?? 'Utilisation',
                'notes' => $notes,
            ]);

            return $movement;
        });
    }

    /**
     * Ajuster le stock (correction manuelle)
     */
    public function adjustStock(Material $material, float $newQuantity, ?string $reason = null, ?string $notes = null): StockMovement
    {
        return DB::transaction(function () use ($material, $newQuantity, $reason, $notes) {
            $stockBefore = $material->stock_quantity;
            $difference = $newQuantity - $stockBefore;

            // Mettre à jour le stock du matériau
            $material->stock_quantity = $newQuantity;
            $material->save();

            // Enregistrer le mouvement
            $movement = StockMovement::create([
                'material_id' => $material->id,
                'project_id' => null,
                'user_id' => Auth::id(),
                'type' => 'adjustment',
                'quantity' => $difference,
                'stock_before' => $stockBefore,
                'stock_after' => $newQuantity,
                'reason' => $reason ?? 'Ajustement manuel',
                'notes' => $notes,
            ]);

            return $movement;
        });
    }

    /**
     * Mettre à jour le stock lors d'une livraison dans un projet
     * @param ProjectMaterial $projectMaterial Le ProjectMaterial mis à jour
     * @param float $newDeliveredQuantity La nouvelle quantité livrée
     * @param float|null $oldDeliveredQuantity L'ancienne quantité livrée (doit être passée depuis le contrôleur)
     */
    public function handleDelivery(ProjectMaterial $projectMaterial, float $newDeliveredQuantity, ?float $oldDeliveredQuantity = null): void
    {
        // Si oldDeliveredQuantity n'est pas fourni, utiliser la valeur actuelle (pour compatibilité)
        $oldDelivered = $oldDeliveredQuantity ?? $projectMaterial->quantity_delivered;
        $difference = $newDeliveredQuantity - $oldDelivered;

        if ($difference > 0) {
            // Livraison : ajouter au stock
            $this->addStock(
                $projectMaterial->material,
                $difference,
                $projectMaterial->project,
                'Livraison projet',
                "Livraison pour le projet: {$projectMaterial->project->name}",
                null
            );
        } elseif ($difference < 0) {
            // Correction : retirer du stock
            $this->removeStock(
                $projectMaterial->material,
                abs($difference),
                $projectMaterial->project,
                'Correction livraison',
                "Correction de livraison pour le projet: {$projectMaterial->project->name}"
            );
        }
    }

    /**
     * Mettre à jour le stock lors d'une utilisation dans un projet
     * @param ProjectMaterial $projectMaterial Le ProjectMaterial mis à jour
     * @param float $newUsedQuantity La nouvelle quantité utilisée
     * @param float|null $oldUsedQuantity L'ancienne quantité utilisée (doit être passée depuis le contrôleur)
     */
    public function handleUsage(ProjectMaterial $projectMaterial, float $newUsedQuantity, ?float $oldUsedQuantity = null): void
    {
        // Si oldUsedQuantity n'est pas fourni, utiliser la valeur actuelle (pour compatibilité)
        $oldUsed = $oldUsedQuantity ?? $projectMaterial->quantity_used;
        $difference = $newUsedQuantity - $oldUsed;

        if ($difference > 0) {
            // Utilisation : retirer du stock
            $this->removeStock(
                $projectMaterial->material,
                $difference,
                $projectMaterial->project,
                'Utilisation projet',
                "Utilisation pour le projet: {$projectMaterial->project->name}"
            );
        } elseif ($difference < 0) {
            // Correction : remettre au stock
            $this->addStock(
                $projectMaterial->material,
                abs($difference),
                $projectMaterial->project,
                'Correction utilisation',
                "Correction d'utilisation pour le projet: {$projectMaterial->project->name}",
                null
            );
        }
    }
}

