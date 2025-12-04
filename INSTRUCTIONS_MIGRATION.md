# Instructions pour exécuter les migrations et seeders

## Problème
La colonne `guard_name` n'existe pas encore dans la table `roles`. Il faut d'abord exécuter la migration qui l'ajoute.

## Solution

### Étape 1 : Exécuter la migration
```bash
php artisan migrate
```

Cette commande exécutera la migration `2025_12_04_220000_adapt_roles_for_spatie_permissions.php` qui :
- Ajoute la colonne `guard_name` à la table `roles` existante
- Crée les tables Spatie Permissions (permissions, model_has_permissions, model_has_roles, role_has_permissions)

### Étape 2 : Exécuter les seeders dans l'ordre
```bash
# 1. Créer toutes les permissions
php artisan db:seed --class=PagePermissionSeeder

# 2. Créer les rôles et assigner les permissions
php artisan db:seed --class=RolePermissionSeeder

# 3. Créer le super admin et lui assigner le rôle super_admin
php artisan db:seed --class=SuperAdminSeeder
```

### Ou exécuter tous les seeders en une fois
```bash
php artisan db:seed
```

## Vérification

Après avoir exécuté les migrations et seeders, vous pouvez vérifier :

1. La table `roles` doit avoir la colonne `guard_name`
2. La table `permissions` doit contenir toutes les permissions
3. Le rôle `super_admin` doit exister avec toutes les permissions
4. Le super admin (admin@admin.com) doit avoir le rôle `super_admin`

## Si la migration échoue

Si la migration échoue parce que la table `roles` existe déjà mais sans `guard_name`, vous pouvez exécuter manuellement :

```sql
ALTER TABLE roles ADD COLUMN guard_name VARCHAR(255) DEFAULT 'web' AFTER name;
```

Puis mettre à jour les rôles existants :
```sql
UPDATE roles SET guard_name = 'web' WHERE guard_name IS NULL OR guard_name = '';
```

Ensuite, exécutez à nouveau la migration pour créer les autres tables.

