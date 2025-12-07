# Correction du problème 404 pour le Check-in

## Problème
L'erreur 404 apparaît lors du check-in sur le serveur de production alors que la route est correctement configurée.

**URL appelée** : `POST https://chantix.universaltechnologiesafrica.com/api/v1/projects/2/attendances/check-in`

## Solution

### 1. Déployer les modifications sur le serveur

**ÉTAPE CRITIQUE** : Connectez-vous au serveur et vérifiez que le fichier `routes/api.php` contient bien :

```php
// Pointage
Route::prefix('projects/{projectId}')->group(function () {
    Route::get('/attendances', [\App\Http\Controllers\Api\AttendanceController::class, 'index']);
    Route::post('/attendances/check-in', [\App\Http\Controllers\Api\AttendanceController::class, 'checkIn']);
    Route::post('/attendances/{attendance}/check-out', [\App\Http\Controllers\Api\AttendanceController::class, 'checkOut']);
    Route::post('/attendances/absence', [\App\Http\Controllers\Api\AttendanceController::class, 'absence']);
});
```

**IMPORTANT** : 
- La route doit utiliser `{projectId}` et **NON** `{project}`
- Vérifiez la ligne 47 du fichier `routes/api.php`

### 2. Vider tous les caches Laravel sur le serveur

**ÉTAPE OBLIGATOIRE** : Connectez-vous à votre serveur et exécutez ces commandes :

```bash
# Se connecter au serveur
ssh user@chantix.universaltechnologiesafrica.com

# Aller dans le répertoire du projet
cd /chemin/vers/votre/projet/chantix

# Vider TOUS les caches (CRITIQUE)
php artisan route:clear
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Redémarrer PHP-FPM (OBLIGATOIRE si OPcache est activé)
sudo service php8.1-fpm restart
# ou selon votre version
sudo service php8.2-fpm restart
sudo service php-fpm restart
```

### 3. Vérifier que les routes sont bien enregistrées

```bash
php artisan route:list | grep check-in
```

Vous devriez voir :
```
POST  api/v1/projects/{projectId}/attendances/check-in
```

### 4. Vérifier les logs Laravel

Consultez les logs pour voir si la requête arrive au serveur :

```bash
tail -f storage/logs/laravel.log
```

Si vous voyez les logs "Check-in appelé" que nous avons ajoutés, cela signifie que la requête arrive bien au serveur.

### 5. Redémarrer les services web (si nécessaire)

```bash
# Nginx
sudo service nginx restart

# Apache
sudo service apache2 restart
```

## Vérification de l'URL

L'application Flutter envoie la requête à :
```
POST https://chantix.universaltechnologiesafrica.com/api/v1/projects/{projectId}/attendances/check-in
```

Assurez-vous que cette URL correspond bien à la route définie dans `routes/api.php`.

## Commandes rapides (copier-coller)

**Sur le serveur de production** :

```bash
cd /chemin/vers/votre/projet/chantix && \
php artisan route:clear && \
php artisan config:clear && \
php artisan cache:clear && \
php artisan view:clear && \
sudo service php8.1-fpm restart
```

**Vérification immédiate** :

```bash
# Vérifier que la route existe
php artisan route:list | grep "check-in"

# Vous devriez voir :
# POST  api/v1/projects/{projectId}/attendances/check-in
```

**Si la route n'apparaît pas** :

1. Vérifiez que `routes/api.php` ligne 47 contient bien `projects/{projectId}`
2. Vérifiez que le fichier a bien été déployé (pas de conflit Git)
3. Vérifiez les permissions : `chmod 644 routes/api.php`
4. Redémarrez PHP-FPM : `sudo service php-fpm restart`

## Notes importantes

1. **Le cache des routes** : Laravel met en cache les routes en production. Il est essentiel de vider ce cache après chaque modification des routes.

2. **OPcache** : Si OPcache est activé, les modifications des fichiers PHP ne seront pas prises en compte tant que PHP-FPM n'est pas redémarré.

3. **Permissions** : Assurez-vous que les fichiers ont les bonnes permissions :
   ```bash
   chmod -R 755 storage bootstrap/cache
   chown -R www-data:www-data storage bootstrap/cache
   ```
