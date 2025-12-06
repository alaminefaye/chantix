# Correction du problème 404 sur le serveur - Expenses

## Problème
L'erreur 404 apparaît sur le serveur pour les routes `expenses.show` alors que ça fonctionne en local.

## Solution

### 1. Vider tous les caches Laravel sur le serveur

Connectez-vous à votre serveur et exécutez ces commandes dans le répertoire du projet :

```bash
cd /chemin/vers/votre/projet/chantix

# Vider le cache des routes
php artisan route:clear

# Vider le cache de configuration
php artisan config:clear

# Vider le cache de l'application
php artisan cache:clear

# Vider le cache des vues
php artisan view:clear

# Optimiser pour la production (optionnel mais recommandé)
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 2. Vérifier que les routes sont bien enregistrées

```bash
php artisan route:list --name=expenses.show
```

Vous devriez voir :
```
GET|HEAD  projects/{project}/expenses/{expense} expenses.show
```

### 3. Vérifier les permissions des fichiers

Assurez-vous que les fichiers ont les bonnes permissions :

```bash
# Donner les permissions correctes
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 4. Vérifier que le modèle Expense est bien chargé

Vérifiez que le fichier `app/Models/Expense.php` contient bien la méthode `resolveRouteBinding`.

### 5. Redémarrer les services (si nécessaire)

```bash
# Si vous utilisez PHP-FPM
sudo service php8.1-fpm restart
# ou
sudo service php8.2-fpm restart

# Si vous utilisez Nginx
sudo service nginx restart

# Si vous utilisez Apache
sudo service apache2 restart
```

### 6. Vérifier les logs d'erreur

Consultez les logs Laravel pour voir s'il y a des erreurs :

```bash
tail -f storage/logs/laravel.log
```

## Solution appliquée (NOUVELLE APPROCHE - IMPORTANT !)

Le problème venait du **route model binding dans un contexte de routes imbriquées**. La solution est de récupérer l'expense **via la relation du projet** plutôt que d'utiliser le route model binding automatique.

### Modifications apportées

Le contrôleur `ExpenseController` a été modifié pour utiliser :
```php
$expense = $project->expenses()->findOrFail($expense);
```

Au lieu de :
```php
public function show(Project $project, Expense $expense)
```

Cette approche garantit que :
1. L'expense existe
2. L'expense appartient au projet spécifié
3. Pas de problème avec le route model binding sur le serveur

### Vérification des modifications

1. Vérifiez que le fichier `app/Http/Controllers/ExpenseController.php` contient bien :
   - `public function show(Project $project, $expense)` (pas `Expense $expense`)
   - `$expense = $project->expenses()->findOrFail($expense);` dans la méthode

2. Testez directement la relation :
```bash
php artisan tinker
>>> $project = \App\Models\Project::find(1);
>>> $expense = $project->expenses()->find(3);
>>> $expense;
```

Si l'expense existe via cette relation, le problème vient du cache ou de la configuration.

## Commandes rapides (copier-coller)

```bash
cd /chemin/vers/votre/projet/chantix && \
php artisan route:clear && \
php artisan config:clear && \
php artisan cache:clear && \
php artisan view:clear && \
php artisan config:cache && \
php artisan route:cache
```

