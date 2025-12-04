<?php

/**
 * Script de test pour vérifier les seeders
 * 
 * Exécuter avec: php artisan tinker < test_seeders.php
 * Ou copier-coller dans tinker
 */

use App\Models\Role;
use Spatie\Permission\Models\Permission;

echo "=== Test des Seeders ===\n\n";

// 1. Vérifier que les permissions existent
echo "1. Vérification des permissions...\n";
$permissionsCount = Permission::where('guard_name', 'web')->count();
echo "   Permissions trouvées: {$permissionsCount}\n\n";

if ($permissionsCount === 0) {
    echo "❌ ERREUR: Aucune permission trouvée. Exécutez d'abord PagePermissionSeeder.\n";
    exit(1);
}

// 2. Vérifier que les rôles peuvent être créés
echo "2. Test de création des rôles...\n";
try {
    $testRole = Role::firstOrCreate(
        ['name' => 'test_role', 'guard_name' => 'web'],
        [
            'display_name' => 'Rôle de test',
            'description' => 'Test',
        ]
    );
    echo "   ✓ Rôle créé avec succès (ID: {$testRole->id})\n";
    
    // 3. Tester l'assignation d'une permission
    echo "\n3. Test d'assignation de permission...\n";
    $firstPermission = Permission::where('guard_name', 'web')->first();
    if ($firstPermission) {
        $testRole->givePermissionTo($firstPermission->name);
        echo "   ✓ Permission '{$firstPermission->name}' assignée avec succès\n";
        
        // Vérifier que la permission est bien assignée
        $hasPermission = $testRole->hasPermissionTo($firstPermission->name);
        echo "   ✓ Vérification: " . ($hasPermission ? "OUI" : "NON") . "\n";
    }
    
    // Nettoyer
    $testRole->delete();
    echo "\n   ✓ Rôle de test supprimé\n";
    
} catch (\Exception $e) {
    echo "   ❌ ERREUR: " . $e->getMessage() . "\n";
    echo "   Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}

echo "\n=== Tests terminés avec succès ===\n";
echo "Vous pouvez maintenant exécuter les seeders.\n";

