<?php
/**
 * Script pour corriger les projets assignés à un utilisateur
 * Synchronise project_user avec les projets de l'invitation
 * Usage: php fix_user_projects.php email@example.com [--dry-run]
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Invitation;
use Illuminate\Support\Facades\DB;

$email = $argv[1] ?? null;
$dryRun = in_array('--dry-run', $argv);

if (!$email) {
    echo "Usage: php fix_user_projects.php email@example.com [--dry-run]\n";
    echo "  --dry-run : Affiche ce qui sera fait sans modifier la base de données\n";
    exit(1);
}

echo "=== Correction des projets pour {$email} ===\n\n";

$user = User::where('email', $email)->first();
if (!$user) {
    echo "❌ Utilisateur non trouvé\n";
    exit(1);
}

echo "Utilisateur: {$user->name} ({$user->email})\n";
echo "ID: {$user->id}\n\n";

// 1. Récupérer les projets dans l'invitation
$invitation = Invitation::where('email', $user->email)->first();
if (!$invitation) {
    echo "⚠ Aucune invitation trouvée pour cet utilisateur\n";
    exit(1);
}

$invitationProjects = $invitation->getProjectsDirectly();
$invitationProjectIds = $invitationProjects->pluck('id')->toArray();

echo "--- Projets dans l'invitation ---\n";
echo "Nombre: " . count($invitationProjectIds) . "\n";
foreach ($invitationProjectIds as $projectId) {
    $project = \App\Models\Project::find($projectId);
    echo "  ✓ {$project->name} (ID: {$projectId})\n";
}

// 2. Récupérer les projets actuels dans project_user
$currentProjectIds = DB::table('project_user')
    ->where('user_id', $user->id)
    ->pluck('project_id')
    ->toArray();

echo "\n--- Projets actuels dans project_user ---\n";
echo "Nombre: " . count($currentProjectIds) . "\n";
foreach ($currentProjectIds as $projectId) {
    $project = \App\Models\Project::find($projectId);
    echo "  ✓ {$project->name} (ID: {$projectId})\n";
}

// 3. Identifier les différences
$toAdd = array_diff($invitationProjectIds, $currentProjectIds);
$toRemove = array_diff($currentProjectIds, $invitationProjectIds);

echo "\n--- Actions à effectuer ---\n";

if (empty($toAdd) && empty($toRemove)) {
    echo "✓ Aucune action nécessaire - Les projets sont déjà synchronisés\n";
    exit(0);
}

if (!empty($toAdd)) {
    echo "Projets à AJOUTER:\n";
    foreach ($toAdd as $projectId) {
        $project = \App\Models\Project::find($projectId);
        echo "  + {$project->name} (ID: {$projectId})\n";
    }
}

if (!empty($toRemove)) {
    echo "Projets à SUPPRIMER:\n";
    foreach ($toRemove as $projectId) {
        $project = \App\Models\Project::find($projectId);
        echo "  - {$project->name} (ID: {$projectId})\n";
    }
}

// 4. Appliquer les corrections
if ($dryRun) {
    echo "\n⚠ Mode DRY-RUN - Aucune modification ne sera effectuée\n";
} else {
    echo "\n--- Application des corrections ---\n";
    
    // Supprimer les projets non désirés
    if (!empty($toRemove)) {
        foreach ($toRemove as $projectId) {
            DB::table('project_user')
                ->where('user_id', $user->id)
                ->where('project_id', $projectId)
                ->delete();
            $project = \App\Models\Project::find($projectId);
            echo "  ✓ Supprimé: {$project->name}\n";
        }
    }
    
    // Ajouter les projets manquants
    if (!empty($toAdd)) {
        foreach ($toAdd as $projectId) {
            DB::table('project_user')->insert([
                'user_id' => $user->id,
                'project_id' => $projectId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $project = \App\Models\Project::find($projectId);
            echo "  ✓ Ajouté: {$project->name}\n";
        }
    }
    
    echo "\n✓ Correction terminée!\n";
    
    // Vérification finale
    $finalProjectIds = DB::table('project_user')
        ->where('user_id', $user->id)
        ->pluck('project_id')
        ->toArray();
    
    echo "\n--- Vérification finale ---\n";
    echo "Projets dans project_user: " . count($finalProjectIds) . "\n";
    foreach ($finalProjectIds as $projectId) {
        $project = \App\Models\Project::find($projectId);
        echo "  ✓ {$project->name} (ID: {$projectId})\n";
    }
    
    if ($finalProjectIds === $invitationProjectIds) {
        echo "\n✅ Synchronisation réussie!\n";
    } else {
        echo "\n⚠ ATTENTION: Il reste des différences\n";
    }
}

echo "\n=== Fin ===\n";
