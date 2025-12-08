<?php
/**
 * Script de vérification des projets assignés à un utilisateur
 * Usage: php check_user_projects.php email@example.com
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Invitation;
use Illuminate\Support\Facades\DB;

$email = $argv[1] ?? 'aminefaye@gmail.com';

echo "=== Vérification des projets pour {$email} ===\n\n";

$user = User::where('email', $email)->first();
if (!$user) {
    echo "❌ Utilisateur non trouvé\n";
    exit(1);
}

echo "Utilisateur: {$user->name} ({$user->email})\n";
echo "ID: {$user->id}\n\n";

// 1. Vérifier les projets dans project_user
echo "--- Projets dans project_user ---\n";
$projectIds = DB::table('project_user')
    ->where('user_id', $user->id)
    ->pluck('project_id')
    ->toArray();

echo "Nombre de projets assignés: " . count($projectIds) . "\n";
if (!empty($projectIds)) {
    foreach ($projectIds as $projectId) {
        $project = \App\Models\Project::find($projectId);
        if ($project) {
            echo "  ✓ {$project->name} (ID: {$projectId}, Company: {$project->company_id})\n";
        } else {
            echo "  ⚠ Projet ID {$projectId} n'existe plus\n";
        }
    }
} else {
    echo "  ⚠ Aucun projet assigné\n";
}

// 2. Vérifier les projets dans l'invitation
echo "\n--- Projets dans l'invitation ---\n";
$invitation = Invitation::where('email', $user->email)->first();
if ($invitation) {
    $invitationProjects = $invitation->getProjectsDirectly();
    echo "Nombre de projets dans l'invitation: " . $invitationProjects->count() . "\n";
    if ($invitationProjects->count() > 0) {
        foreach ($invitationProjects as $project) {
            echo "  ✓ {$project->name} (ID: {$project->id})\n";
        }
    } else {
        echo "  ⚠ Aucun projet dans l'invitation\n";
    }
} else {
    echo "  ⚠ Aucune invitation trouvée\n";
}

// 3. Vérifier le rôle
echo "\n--- Rôle et permissions ---\n";
$companyId = $user->current_company_id;
if ($companyId) {
    $role = $user->roleInCompany($companyId);
    echo "Rôle: " . ($role ? $role->name : 'aucun') . "\n";
    echo "Est admin: " . ($user->hasRoleInCompany('admin', $companyId) ? 'Oui ⚠ (voit TOUS les projets)' : 'Non ✓') . "\n";
    echo "Est super admin: " . ($user->isSuperAdmin() ? 'Oui ⚠ (voit TOUS les projets)' : 'Non ✓') . "\n";
} else {
    echo "  ⚠ Aucune entreprise sélectionnée\n";
}

// 4. Vérifier ce que l'API retournerait
echo "\n--- Ce que l'API retournerait ---\n";
if ($companyId) {
    $projects = \App\Models\Project::accessibleByUser($user, $companyId)->get();
    echo "Nombre de projets accessibles: " . $projects->count() . "\n";
    foreach ($projects as $project) {
        echo "  ✓ {$project->name} (ID: {$project->id})\n";
    }
} else {
    echo "  ⚠ Impossible de vérifier (pas d'entreprise)\n";
}

// 5. Vérifier les incohérences
echo "\n--- Vérification des incohérences ---\n";
if ($invitation && !empty($projectIds)) {
    $invitationProjectIds = $invitationProjects->pluck('id')->toArray();
    $diff = array_diff($invitationProjectIds, $projectIds);
    if (!empty($diff)) {
        echo "  ⚠ ATTENTION: Projets dans l'invitation mais PAS dans project_user:\n";
        foreach ($diff as $projectId) {
            $project = \App\Models\Project::find($projectId);
            echo "    - {$project->name} (ID: {$projectId})\n";
        }
    } else {
        echo "  ✓ Cohérence OK: Les projets de l'invitation sont dans project_user\n";
    }
}

echo "\n=== Fin de la vérification ===\n";

