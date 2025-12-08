#!/bin/bash

# Test de la méthode accessibleByUser pour aminefaye@gmail.com

EMAIL="aminefaye@gmail.com"

echo "=========================================="
echo "TEST accessibleByUser pour: $EMAIL"
echo "=========================================="
echo ""

php artisan tinker --execute="
\$user = App\Models\User::where('email', '$EMAIL')->first();
if (!\$user) {
    echo \"❌ Utilisateur non trouvé!\n\";
    exit;
}

\$companyId = \$user->current_company_id;
echo \"User ID: {\$user->id}\n\";
echo \"Company ID: \$companyId\n\";
\$role = \$user->roleInCompany(\$companyId);
echo \"Role: \" . (\$role ? \$role->name : 'Aucun') . \"\n\";
echo \"Is Super Admin: \" . (\$user->isSuperAdmin() ? 'Oui' : 'Non') . \"\n\";
echo \"Is Admin: \" . (\$user->hasRoleInCompany('admin', \$companyId) ? 'Oui' : 'Non') . \"\n\";
echo \"\n\";

// Vérifier les projets assignés
\$assignedProjectIds = DB::table('project_user')
    ->where('user_id', \$user->id)
    ->pluck('project_id')
    ->toArray();
echo \"Projets assignés dans project_user: \" . (empty(\$assignedProjectIds) ? 'AUCUN' : implode(', ', \$assignedProjectIds)) . \"\n\";
echo \"Nombre de projets assignés: \" . count(\$assignedProjectIds) . \"\n\";
echo \"\n\";

// Tester accessibleByUser
echo \"=== TEST accessibleByUser ===\n\";
\$query = App\Models\Project::accessibleByUser(\$user, \$companyId);
\$projects = \$query->get();
echo \"Nombre de projets retournés par accessibleByUser: \" . \$projects->count() . \"\n\";
echo \"IDs des projets retournés: \" . \$projects->pluck('id')->implode(', ') . \"\n\";
echo \"\n\";

foreach (\$projects as \$project) {
    echo \"  - Projet #{\$project->id}: {\$project->name}\n\";
}

echo \"\n\";
echo \"=== Vérification SQL ===\n\";
\$sql = \$query->toSql();
\$bindings = \$query->getBindings();
echo \"SQL: \$sql\n\";
echo \"Bindings: \" . json_encode(\$bindings) . \"\n\";
"
