#!/bin/bash

# Script pour corriger les projets assignés à un utilisateur

EMAIL="aminefaye@gmail.com"

echo "=========================================="
echo "CORRECTION DES PROJETS - $EMAIL"
echo "=========================================="
echo ""

echo "1. Vérification actuelle..."
php artisan tinker --execute="
\$user = App\Models\User::where('email', '$EMAIL')->first();
if (\$user) {
    \$assigned = DB::table('project_user')->where('user_id', \$user->id)->pluck('project_id')->toArray();
    echo \"Projets actuellement assignés: \" . (empty(\$assigned) ? 'AUCUN' : implode(', ', \$assigned)) . \"\n\";
    echo \"Nombre: \" . count(\$assigned) . \"\n\";
}
"

echo ""
echo "2. Pour assigner UN SEUL projet, entrez l'ID du projet:"
echo "   (Laissez vide pour garder les projets actuels)"
read -p "ID du projet à assigner (ou appuyez sur Entrée pour annuler): " PROJECT_ID

if [ ! -z "$PROJECT_ID" ] && [ "$PROJECT_ID" != "" ]; then
    echo ""
    echo "3. Assignation du projet #$PROJECT_ID..."
    php artisan tinker --execute="
    \$user = App\Models\User::where('email', '$EMAIL')->first();
    \$projectId = $PROJECT_ID;
    if (\$user) {
        // Vérifier que le projet existe
        \$project = App\Models\Project::find(\$projectId);
        if (!\$project) {
            echo \"❌ Projet #\$projectId non trouvé!\n\";
            exit;
        }
        
        // Vérifier que le projet appartient à la même entreprise
        if (\$project->company_id != \$user->current_company_id) {
            echo \"❌ Le projet n'appartient pas à la même entreprise!\n\";
            exit;
        }
        
        // Supprimer toutes les assignations existantes
        DB::table('project_user')->where('user_id', \$user->id)->delete();
        
        // Assigner le nouveau projet
        DB::table('project_user')->insert([
            'user_id' => \$user->id,
            'project_id' => \$projectId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        echo \"✅ Projet #\$projectId ({$project->name}) assigné\n\";
        echo \"✅ Toutes les autres assignations ont été supprimées\n\";
    }
    "
    
    echo ""
    echo "4. Vérification finale..."
    php artisan tinker --execute="
    \$user = App\Models\User::where('email', '$EMAIL')->first();
    if (\$user) {
        \$assigned = DB::table('project_user')->where('user_id', \$user->id)->pluck('project_id')->toArray();
        echo \"Projets maintenant assignés: \" . (empty(\$assigned) ? 'AUCUN' : implode(', ', \$assigned)) . \"\n\";
    }
    "
else
    echo "Annulé."
fi

echo ""
echo "=========================================="
echo "TERMINÉ"
echo "=========================================="
