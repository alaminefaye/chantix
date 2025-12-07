# Vérification des Projets Assignés à un Utilisateur

## 🔍 Diagnostic du problème

Si un utilisateur voit plusieurs projets alors qu'un seul lui a été assigné, suivez ces étapes :

### 1. Vérifier les projets assignés dans la base de données

```bash
# Se connecter au serveur
ssh user@votre-serveur.com
cd /chemin/vers/votre/projet

# Ouvrir tinker
php artisan tinker

# Vérifier les projets assignés à un utilisateur spécifique
$user = App\Models\User::where('email', 'aminefaye@gmail.com')->first();
$projectIds = DB::table('project_user')->where('user_id', $user->id)->pluck('project_id');
echo "Projets assignés: " . $projectIds->implode(', ');
echo "\nNombre de projets: " . $projectIds->count();

# Vérifier les détails
foreach ($projectIds as $projectId) {
    $project = App\Models\Project::find($projectId);
    echo "\n- {$project->name} (ID: {$projectId})";
}
```

### 2. Vérifier l'invitation de l'utilisateur

```bash
php artisan tinker

$user = App\Models\User::where('email', 'aminefaye@gmail.com')->first();
$invitation = App\Models\Invitation::where('email', $user->email)->first();

if ($invitation) {
    $invitationProjects = $invitation->getProjectsDirectly();
    echo "Projets dans l'invitation: " . $invitationProjects->pluck('id')->implode(', ');
    echo "\nNombre: " . $invitationProjects->count();
}
```

### 3. Vérifier ce que l'API retourne

```bash
# Tester l'API directement
curl -X GET "https://chantix.universaltechnologiesafrica.com/api/v1/projects" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Accept: application/json"
```

### 4. Vérifier les logs

```bash
# Voir les logs de l'API
tail -f storage/logs/laravel.log | grep "API Projects"
```

## 🐛 Problèmes courants

### Problème 1: L'utilisateur a plusieurs projets dans project_user

**Solution:** Vérifier et nettoyer la table `project_user` :

```bash
php artisan tinker

$user = App\Models\User::where('email', 'aminefaye@gmail.com')->first();

# Voir tous les projets assignés
DB::table('project_user')->where('user_id', $user->id)->get();

# Supprimer les projets non désirés (ATTENTION: remplacez X par l'ID du projet à supprimer)
DB::table('project_user')
    ->where('user_id', $user->id)
    ->where('project_id', X) // ID du projet à supprimer
    ->delete();
```

### Problème 2: L'invitation a plusieurs projets assignés

**Solution:** Modifier l'invitation pour ne garder qu'un seul projet :

1. Aller sur le site web
2. Modifier l'invitation
3. Désélectionner les projets non désirés
4. Enregistrer

### Problème 3: L'utilisateur est admin

**Solution:** Si l'utilisateur a le rôle "admin", il verra TOUS les projets. C'est normal.

Vérifier le rôle :
```bash
php artisan tinker

$user = App\Models\User::where('email', 'aminefaye@gmail.com')->first();
$companyId = 1; // Remplacez par l'ID de l'entreprise
$role = $user->roleInCompany($companyId);
echo "Rôle: " . ($role ? $role->name : 'aucun');
echo "\nEst admin: " . ($user->hasRoleInCompany('admin', $companyId) ? 'Oui' : 'Non');
```

## ✅ Script de vérification automatique

Créez un fichier `check_user_projects.php` :

```php
<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$email = $argv[1] ?? 'aminefaye@gmail.com';

$user = App\Models\User::where('email', $email)->first();
if (!$user) {
    echo "Utilisateur non trouvé\n";
    exit(1);
}

echo "=== Vérification pour {$user->email} ===\n\n";

// Projets dans project_user
$projectIds = DB::table('project_user')
    ->where('user_id', $user->id)
    ->pluck('project_id')
    ->toArray();

echo "Projets dans project_user: " . count($projectIds) . "\n";
foreach ($projectIds as $projectId) {
    $project = App\Models\Project::find($projectId);
    echo "  - {$project->name} (ID: {$projectId})\n";
}

// Projets dans l'invitation
$invitation = App\Models\Invitation::where('email', $user->email)->first();
if ($invitation) {
    $invitationProjects = $invitation->getProjectsDirectly();
    echo "\nProjets dans l'invitation: " . $invitationProjects->count() . "\n";
    foreach ($invitationProjects as $project) {
        echo "  - {$project->name} (ID: {$project->id})\n";
    }
}

// Rôle
$companyId = $user->current_company_id;
if ($companyId) {
    $role = $user->roleInCompany($companyId);
    echo "\nRôle: " . ($role ? $role->name : 'aucun');
    echo "\nEst admin: " . ($user->hasRoleInCompany('admin', $companyId) ? 'Oui' : 'Non');
}

echo "\n\n";
```

Utilisation :
```bash
php check_user_projects.php aminefaye@gmail.com
```
