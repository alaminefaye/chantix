# Test de la route Check-in sur le serveur

## La route est bien enregistrée ✅

```
POST  api/v1/projects/{projectId}/attendances/check-in
```

## Prochaines étapes de diagnostic

### 1. Vérifier que le cache des routes est vraiment vidé

```bash
# Vérifier si un fichier de cache existe
ls -la bootstrap/cache/routes*.php

# Si des fichiers existent, les supprimer
rm -f bootstrap/cache/routes*.php

# Vider à nouveau
php artisan route:clear
```

### 2. Tester la route directement avec curl

```bash
# Récupérer votre token d'authentification depuis l'application Flutter
# Puis tester la route :

curl -X POST \
  https://chantix.universaltechnologiesafrica.com/api/v1/projects/2/attendances/check-in \
  -H "Authorization: Bearer VOTRE_TOKEN_ICI" \
  -H "Content-Type: multipart/form-data" \
  -F "check_in_latitude=5.252433" \
  -F "check_in_longitude=-3.944041"

# Si vous obtenez une réponse (même une erreur 422 ou autre), la route fonctionne
# Si vous obtenez 404, le problème vient du cache ou de la configuration
```

### 3. Vérifier les logs en temps réel

```bash
# Dans un terminal, surveiller les logs
tail -f storage/logs/laravel.log

# Puis dans l'application Flutter, essayez de faire un check-in
# Si vous voyez "Check-in appelé" dans les logs, la requête arrive au serveur
```

### 4. Vérifier le fichier de cache des routes

```bash
# Si le fichier bootstrap/cache/routes-v7.php existe, le supprimer
rm -f bootstrap/cache/routes-v7.php
php artisan route:clear
```

### 5. Vérifier les permissions

```bash
# S'assurer que Laravel peut écrire dans bootstrap/cache
chmod -R 775 bootstrap/cache
chown -R www-data:www-data bootstrap/cache
```

### 6. Solution alternative : Forcer la régénération du cache

```bash
# Supprimer tous les fichiers de cache
rm -rf bootstrap/cache/*.php
rm -rf storage/framework/cache/data/*
rm -rf storage/framework/views/*

# Régénérer
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Puis tester à nouveau
```

### 7. Si rien ne fonctionne : Désactiver temporairement le cache des routes

Éditez `app/Providers/RouteServiceProvider.php` et commentez la ligne qui cache les routes :

```php
// public function boot(): void
// {
//     $this->routes(function () {
//         // ...
//     });
//     
//     // Commenter cette ligne temporairement
//     // if (app()->environment('production')) {
//     //     Route::middleware('web')->group(base_path('routes/web.php'));
//     // }
// }
```

Puis redémarrez PHP-FPM.

## Diagnostic rapide

Exécutez cette commande pour voir toutes les informations :

```bash
php artisan route:list --path=attendances/check-in --columns=method,uri,action
```

Vous devriez voir exactement :
```
POST  api/v1/projects/{projectId}/attendances/check-in  Api\AttendanceController@checkIn
```

Si vous voyez `{project}` au lieu de `{projectId}`, le fichier `routes/api.php` n'a pas été mis à jour correctement.

