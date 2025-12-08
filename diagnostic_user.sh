#!/bin/bash

# Script de diagnostic pour l'utilisateur aminefaye@gmail.com

EMAIL="aminefaye@gmail.com"

echo "=========================================="
echo "DIAGNOSTIC UTILISATEUR: $EMAIL"
echo "=========================================="
echo ""

echo "1. Vérification de l'utilisateur et projets assignés..."
php artisan user:check-projects $EMAIL

echo ""
echo "2. Vérification directe dans la base de données..."
php artisan tinker --execute="
\$user = App\Models\User::where('email', '$EMAIL')->first();
if (\$user) {
    echo \"User ID: {\$user->id}\n\";
    echo \"Name: {\$user->name}\n\";
    echo \"Company ID: {\$user->current_company_id}\n\";
    \$role = \$user->roleInCompany(\$user->current_company_id);
    echo \"Role: \" . (\$role ? \$role->name : 'Aucun') . \"\n\";
    echo \"Is Super Admin: \" . (\$user->isSuperAdmin() ? 'Oui' : 'Non') . \"\n\";
    echo \"Is Admin: \" . (\$user->hasRoleInCompany('admin', \$user->current_company_id) ? 'Oui' : 'Non') . \"\n\";
    \$assigned = DB::table('project_user')->where('user_id', \$user->id)->pluck('project_id')->toArray();
    echo \"Projets assignés: \" . (empty(\$assigned) ? 'AUCUN ⚠️' : implode(', ', \$assigned)) . \"\n\";
    echo \"Nombre de projets assignés: \" . count(\$assigned) . \"\n\";
    if (empty(\$assigned)) {
        echo \"\n⚠️  ATTENTION: Aucun projet assigné! L'utilisateur verra tous les projets s'il est superviseur/ingénieur.\n\";
    }
} else {
    echo \"❌ Utilisateur non trouvé!\n\";
}
"

echo ""
echo "3. Vérification des invitations acceptées..."
php artisan tinker --execute="
\$invitations = App\Models\Invitation::where('email', '$EMAIL')->where('status', 'accepted')->get();
if (\$invitations->count() > 0) {
    foreach (\$invitations as \$inv) {
        echo \"Invitation ID: {\$inv->id}\n\";
        \$projects = \$inv->getProjectsDirectly();
        echo \"Projets dans l'invitation: \" . (\$projects->isEmpty() ? 'AUCUN' : \$projects->pluck('id')->implode(', ')) . \"\n\";
    }
} else {
    echo \"Aucune invitation acceptée trouvée.\n\";
}
"

echo ""
echo "4. Liste de tous les projets de l'entreprise et accès utilisateur..."
php artisan tinker --execute="
\$user = App\Models\User::where('email', '$EMAIL')->first();
if (\$user && \$user->current_company_id) {
    \$projects = App\Models\Project::where('company_id', \$user->current_company_id)->get();
    echo \"Total projets dans l'entreprise: \" . \$projects->count() . \"\n\n\";
    foreach (\$projects as \$project) {
        echo \"Projet #{\$project->id}: {\$project->name}\n\";
        \$users = DB::table('project_user')->where('project_id', \$project->id)->pluck('user_id')->toArray();
        echo \"  Utilisateurs assignés: \" . (empty(\$users) ? 'AUCUN' : implode(', ', \$users)) . \"\n\";
        if (in_array(\$user->id, \$users)) {
            echo \"  ✅ L'utilisateur a accès à ce projet\n\";
        } else {
            echo \"  ❌ L'utilisateur N'A PAS accès à ce projet\n\";
        }
        echo \"\n\";
    }
}
"

echo ""
echo "5. Vérification des logs récents (accessibleByUser)..."
tail -n 50 storage/logs/laravel.log | grep -A 3 -B 3 "accessibleByUser\|API Projects" | tail -n 20 || echo "Aucun log récent trouvé"

echo ""
echo "=========================================="
echo "DIAGNOSTIC TERMINÉ"
echo "=========================================="
