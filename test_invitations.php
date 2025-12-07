<?php
/**
 * Script de test pour vérifier les projets des invitations sur le serveur
 * Usage: php test_invitations.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Invitation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== Test des projets des invitations ===\n\n";

// Vérifier que la table existe
if (!Schema::hasTable('invitation_project')) {
    echo "❌ ERREUR: La table 'invitation_project' n'existe pas!\n";
    echo "Exécutez: php artisan migrate\n";
    exit(1);
}

echo "✓ Table 'invitation_project' existe\n\n";

// Récupérer toutes les invitations
$invitations = Invitation::all();

if ($invitations->isEmpty()) {
    echo "⚠ Aucune invitation trouvée\n";
    exit(0);
}

echo "Nombre d'invitations: " . $invitations->count() . "\n\n";

foreach ($invitations as $invitation) {
    echo "--- Invitation #{$invitation->id} ({$invitation->email}) ---\n";
    
    // Méthode 1: Requête directe sur la table pivot
    $directProjectIds = DB::table('invitation_project')
        ->where('invitation_id', $invitation->id)
        ->pluck('project_id')
        ->toArray();
    
    echo "  IDs dans table pivot: " . (empty($directProjectIds) ? 'Aucun' : implode(', ', $directProjectIds)) . "\n";
    echo "  Nombre dans table pivot: " . count($directProjectIds) . "\n";
    
    // Méthode 2: Utiliser getProjectsDirectly()
    $projects = $invitation->getProjectsDirectly();
    echo "  Nombre avec getProjectsDirectly(): " . $projects->count() . "\n";
    
    if ($projects->count() > 0) {
        echo "  Projets:\n";
        foreach ($projects as $project) {
            echo "    - {$project->name} (ID: {$project->id})\n";
        }
    } else {
        echo "  ⚠ Aucun projet trouvé\n";
    }
    
    // Méthode 3: Relation Eloquent (pour comparaison)
    $invitation->load('projects');
    $relationCount = $invitation->projects->count();
    echo "  Nombre avec relation Eloquent: " . $relationCount . "\n";
    
    // Vérifier la cohérence
    if (count($directProjectIds) !== $projects->count()) {
        echo "  ⚠ ATTENTION: Incohérence entre table pivot et getProjectsDirectly()\n";
    }
    if (count($directProjectIds) !== $relationCount) {
        echo "  ⚠ ATTENTION: Incohérence entre table pivot et relation Eloquent\n";
    }
    
    echo "\n";
}

echo "=== Test terminé ===\n";
