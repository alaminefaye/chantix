# Commandes de Diagnostic et Correction

## 1. Vérifier l'état de l'utilisateur et ses projets assignés

```bash
php artisan user:check-projects aminefaye@gmail.com
```

## 2. Vérifier les projets assignés directement dans la base de données

```bash
php artisan tinker --execute="
\$user = App\Models\User::where('email', 'aminefaye@gmail.com')->first();
if (\$user) {
    echo \"User ID: {\$user->id}\n\";
    echo \"Name: {\$user->name}\n\";
    echo \"Company ID: {\$user->current_company_id}\n\";
    \$role = \$user->roleInCompany(\$user->current_company_id);
    echo \"Role: \" . (\$role ? \$role->name : 'Aucun') . \"\n\";
    \$assigned = DB::table('project_user')->where('user_id', \$user->id)->pluck('project_id')->toArray();
    echo \"Projets assignés: \" . (empty(\$assigned) ? 'AUCUN' : implode(', ', \$assigned)) . \"\n\";
    echo \"Nombre de projets assignés: \" . count(\$assigned) . \"\n\";
} else {
    echo \"Utilisateur non trouvé!\n\";
}
"
```

## 3. Vérifier les invitations acceptées pour cet utilisateur

```bash
php artisan tinker --execute="
\$invitations = App\Models\Invitation::where('email', 'aminefaye@gmail.com')->where('status', 'accepted')->get();
foreach (\$invitations as \$inv) {
    echo \"Invitation ID: {\$inv->id}\n\";
    \$projects = \$inv->getProjectsDirectly();
    echo \"Projets dans l'invitation: \" . \$projects->pluck('id')->implode(', ') . \"\n\";
}
"
```

## 4. Corriger les assignations de projets manquantes

```bash
php artisan user:fix-project-assignments
```

## 5. Vérifier les logs récents pour voir ce qui se passe

```bash
tail -n 100 storage/logs/laravel.log | grep -A 5 -B 5 "accessibleByUser\|API Projects"
```

## 6. Si l'utilisateur n'a pas de projets assignés, les assigner manuellement

Remplacez `USER_ID` et `PROJECT_ID` par les vrais IDs :

```bash
php artisan tinker --execute="
\$user = App\Models\User::where('email', 'aminefaye@gmail.com')->first();
if (\$user && \$user->current_company_id) {
    // Récupérer le premier projet de l'entreprise (ou spécifiez l'ID du projet)
    \$project = App\Models\Project::where('company_id', \$user->current_company_id)->first();
    if (\$project) {
        \$exists = DB::table('project_user')
            ->where('user_id', \$user->id)
            ->where('project_id', \$project->id)
            ->exists();
        if (!\$exists) {
            DB::table('project_user')->insert([
                'user_id' => \$user->id,
                'project_id' => \$project->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            echo \"✅ Projet #{\$project->id} ({$project->name}) assigné à l'utilisateur\n\";
        } else {
            echo \"ℹ️  Le projet est déjà assigné\n\";
        }
    }
}
"
```

## 7. Vérifier tous les projets de l'entreprise et qui y a accès

```bash
php artisan tinker --execute="
\$user = App\Models\User::where('email', 'aminefaye@gmail.com')->first();
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
```

## 8. Forcer la réassignation d'un projet spécifique

Si vous connaissez l'ID du projet à assigner (remplacez PROJECT_ID) :

```bash
php artisan tinker --execute="
\$user = App\Models\User::where('email', 'aminefaye@gmail.com')->first();
\$projectId = PROJECT_ID; // Remplacez par l'ID du projet
if (\$user) {
    // Supprimer toutes les assignations existantes
    DB::table('project_user')->where('user_id', \$user->id)->delete();
    // Assigner le projet spécifique
    DB::table('project_user')->insert([
        'user_id' => \$user->id,
        'project_id' => \$projectId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    echo \"✅ Projet #{\$projectId} assigné (toutes les autres assignations supprimées)\n\";
}
"
```
