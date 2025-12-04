# Configuration Spatie Permissions

## Installation

1. Installer le package Spatie Permissions :
```bash
composer require spatie/laravel-permission
```

2. Publier les migrations :
```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

3. Exécuter les migrations :
```bash
php artisan migrate
```

4. Exécuter les seeders pour créer les permissions et assigner les rôles :
```bash
php artisan db:seed --class=PagePermissionSeeder
php artisan db:seed --class=RolePermissionSeeder
```

Ou exécuter tous les seeders :
```bash
php artisan db:seed
```

## Structure

### Permissions créées

Le seeder `PagePermissionSeeder` crée toutes les permissions nécessaires pour :
- Dashboard
- Companies
- Users
- Projects
- Materials
- Employees
- Attendances
- Expenses
- Tasks
- Reports
- Progress
- Comments
- Profile
- Notifications
- Admin

### Rôles et permissions assignées

Le seeder `RolePermissionSeeder` crée les rôles suivants et leur assigne les permissions :

1. **admin** : Toutes les permissions
2. **chef_chantier** : Gestion complète des chantiers
3. **ingenieur** : Suivi technique et validation
4. **ouvrier** : Pointage et mises à jour
5. **comptable** : Gestion financière
6. **superviseur** : Vue d'ensemble et rapports

## Utilisation dans les vues

Utiliser la directive `@can` pour protéger les éléments :

```blade
@can('projects.create')
    <a href="{{ route('projects.create') }}">Créer un projet</a>
@endcan
```

## Utilisation dans les contrôleurs

```php
if (!$user->can('users.create')) {
    abort(403, 'Vous n\'avez pas la permission de créer des utilisateurs.');
}
```

## Migration depuis l'ancien système

L'ancien système utilisait un modèle `Role` personnalisé avec des permissions JSON. Le nouveau système utilise Spatie Permissions qui stocke les rôles et permissions dans des tables séparées.

Les méthodes `hasPermission()` et `hasRoleInCompany()` dans le modèle `User` ont été mises à jour pour utiliser Spatie Permissions tout en conservant la compatibilité avec le système multi-entreprises.

