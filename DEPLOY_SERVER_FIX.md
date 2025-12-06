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

## Si le problème persiste

1. Vérifiez que le fichier `app/Http/Controllers/ExpenseController.php` a bien été mis à jour avec les modifications (paramètre `Expense $expense` au lieu de `$expense`)

2. Vérifiez que le fichier `app/Models/Expense.php` contient bien la méthode `resolveRouteBinding`

3. Testez directement la route :
```bash
php artisan tinker
>>> $expense = \App\Models\Expense::find(3);
>>> $expense;
```

Si l'expense existe, le problème vient du route model binding ou du cache.

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

