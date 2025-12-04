# Instructions pour tester les seeders

## Problème rencontré
L'erreur `Call to a member function map() on null` se produit lorsque Spatie essaie d'accéder à la relation `permissions` qui n'est pas initialisée.

## Solution implémentée
J'ai modifié le seeder pour utiliser `givePermissionTo()` avec une permission à la fois dans une boucle, ce qui permet à Spatie d'initialiser la relation au fur et à mesure.

## Tests à effectuer

### 1. Vérifier que les migrations sont à jour
```bash
php artisan migrate:status
```

### 2. Exécuter les migrations si nécessaire
```bash
php artisan migrate
```

### 3. Exécuter les seeders dans l'ordre

#### Étape 1 : Créer les permissions
```bash
php artisan db:seed --class=PagePermissionSeeder
```

#### Étape 2 : Créer les rôles et assigner les permissions
```bash
php artisan db:seed --class=RolePermissionSeeder
```

Si vous obtenez encore l'erreur, essayez cette alternative :

```bash
php artisan tinker
```

Puis dans tinker :
```php
use App\Models\Role;
use Spatie\Permission\Models\Permission;

// Créer le rôle super_admin
$superAdminRole = Role::firstOrCreate(
    ['name' => 'super_admin', 'guard_name' => 'web'],
    ['display_name' => 'Super Administrateur', 'description' => 'Accès complet']
);

// Assigner les permissions une par une
$permissions = Permission::where('guard_name', 'web')->get();
foreach ($permissions as $permission) {
    $superAdminRole->givePermissionTo($permission->name);
}
```

#### Étape 3 : Créer le super admin
```bash
php artisan db:seed --class=SuperAdminSeeder
```

### 4. Vérifier que tout fonctionne

```bash
php artisan tinker
```

```php
use App\Models\Role;
use App\Models\User;

// Vérifier les rôles
Role::all()->pluck('name');

// Vérifier le super admin
$superAdmin = User::where('email', 'admin@admin.com')->first();
$superAdmin->getAllPermissions()->pluck('name');
```

## Si le problème persiste

Si vous obtenez toujours l'erreur, cela peut être dû à :
1. La table `role_has_permissions` n'existe pas → Exécutez `php artisan migrate`
2. La colonne `guard_name` n'existe pas dans `roles` → Exécutez `php artisan migrate`
3. Un problème de cache → Exécutez `php artisan cache:clear` et `php artisan config:clear`

## Alternative : Utiliser directement SQL

Si rien ne fonctionne, vous pouvez insérer directement dans la base de données :

```sql
-- Vérifier que les permissions existent
SELECT COUNT(*) FROM permissions WHERE guard_name = 'web';

-- Vérifier que les rôles existent
SELECT * FROM roles WHERE guard_name = 'web';

-- Assigner toutes les permissions au super_admin (remplacer les IDs)
INSERT INTO role_has_permissions (permission_id, role_id, guard_name)
SELECT p.id, r.id, 'web'
FROM permissions p
CROSS JOIN roles r
WHERE r.name = 'super_admin' AND r.guard_name = 'web'
AND p.guard_name = 'web'
ON DUPLICATE KEY UPDATE permission_id = permission_id;
```

