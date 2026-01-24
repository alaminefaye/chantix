<?php
/**
 * Script pour corriger les projets assignés à un utilisateur
 * Usage: php fix_user_projects.php email@example.com
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Invitation;
use Illuminate\Support\Facades\DB;

$email = $argv[1] ?? 'aminefaye@gmail.com';

echo "=== Correction des projets pour {$email} ===\n\n";

$user = User::where('email', $email)->first();
if (!$user) {
    echo "❌ Utilisateur non trouvé\n";
    exit(1);
}

echo "Utilisateur: {$user->name} ({$user->email})\n";
echo "ID: {$user->id}\n\n";

// Récupérer l'invitation
$invitation = Invitation::where('email', $user->email)->first();
if (!$invitation) {
    echo "❌ Aucune invitation trouvée\n";
    exit(1);
}

// Récupérer les projets de l'invitation
$invitationProjects = $invitation->getProjectsDirectly();
$invitationProjectIds = $invitationProjects->pluck('id')->toArray();

echo "--- Projets dans l'invitation ---\n";
echo "Nombre: " . count($invitationProjectIds) . "\n";
foreach ($invitationProjectIds as $projectId) {
    $project = \App\Models\Project::find($projectId);
    echo "  ✓ {$project->name} (ID: {$projectId})\n";
}

// Récupérer les projets actuels dans project_user
$currentProjectIds = DB::table('project_user')
    ->where('user_id', $user->id)
    ->pluck('project_id')
    ->toArray();

echo "\n--- Projets actuels dans project_user ---\n";
echo "Nombre: " . count($currentProjectIds) . "\n";
foreach ($currentProjectIds as $projectId) {
    $project = \App\Models\Project::find($projectId);
    echo "  - {$project->name} (ID: {$projectId})\n";
}

// Identifier les projets à supprimer
$projectsToRemove = array_diff($currentProjectIds, $invitationProjectIds);

if (!empty($projectsToRemove)) {
    echo "\n--- Projets à supprimer ---\n";
    foreach ($projectsToRemove as $projectId) {
        $project = \App\Models\Project::find($projectId);
        echo "  ✗ {$project->name} (ID: {$projectId})\n";
    }
    
    echo "\n⚠ Suppression des projets non désirés...\n";
    DB::table('project_user')
        ->where('user_id', $user->id)
        ->whereIn('project_id', $projectsToRemove)
        ->delete();
    
    echo "✓ Projets supprimés\n";
} else {
    echo "\n✓ Aucun projet à supprimer\n";
}

// Identifier les projets à ajouter
$projectsToAdd = array_diff($invitationProjectIds, $currentProjectIds);

if (!empty($projectsToAdd)) {
    echo "\n--- Projets à ajouter ---\n";
    foreach ($projectsToAdd as $projectId) {
        $project = \App\Models\Project::find($projectId);
        echo "  + {$project->name} (ID: {$projectId})\n";
    }
    
    echo "\n⚠ Ajout des projets manquants...\n";
    foreach ($projectsToAdd as $projectId) {
        DB::table('project_user')->insert([
            'user_id' => $user->id,
            'project_id' => $projectId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
    
    echo "✓ Projets ajoutés\n";
} else {
    echo "\n✓ Aucun projet à ajouter\n";
}

// Vérification finale
echo "\n--- Vérification finale ---\n";
$finalProjectIds = DB::table('project_user')
    ->where('user_id', $user->id)
    ->pluck('project_id')
    ->toArray();

echo "Nombre de projets après correction: " . count($finalProjectIds) . "\n";
foreach ($finalProjectIds as $projectId) {
    $project = \App\Models\Project::find($projectId);
    echo "  ✓ {$project->name} (ID: {$projectId})\n";
}

// Vérifier la cohérence
if (count($finalProjectIds) === count($invitationProjectIds) && 
    empty(array_diff($finalProjectIds, $invitationProjectIds))) {
    echo "\n✅ CORRECTION RÉUSSIE: Les projets correspondent maintenant à l'invitation\n";
} else {
    echo "\n⚠ ATTENTION: Il y a encore des différences\n";
}

echo "\n=== Fin de la correction ===\n";




