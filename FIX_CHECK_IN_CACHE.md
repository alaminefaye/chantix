# Solution au problème 404 Check-in (Route existe mais 404)

## ✅ La route est bien enregistrée !

```
POST  api/v1/projects/{projectId}/attendances/check-in
```

Le problème vient donc du **cache** ou de la **configuration**.

## Solution immédiate

### Étape 1 : Supprimer TOUS les fichiers de cache

```bash
# Supprimer les fichiers de cache des routes
rm -f bootstrap/cache/routes*.php
rm -f bootstrap/cache/config.php
rm -f bootstrap/cache/services.php

# Supprimer le cache de l'application
rm -rf storage/framework/cache/data/*
rm -rf storage/framework/views/*

# Vider avec artisan
php artisan route:clear
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### Étape 2 : Redémarrer PHP-FPM (CRITIQUE)

```bash
# Trouver votre version de PHP-FPM
sudo service php8.1-fpm restart
# ou
sudo service php8.2-fpm restart
# ou
sudo service php-fpm restart

# Vérifier que le service a redémarré
sudo service php-fpm status
```

### Étape 3 : Vérifier que le cache est bien vidé

```bash
# Vérifier qu'il n'y a plus de fichiers de cache
ls -la bootstrap/cache/routes*.php
# Ne doit rien retourner

# Vérifier les routes à nouveau
php artisan route:list | grep check-in
```

### Étape 4 : Tester avec curl (optionnel mais recommandé)

```bash
# Récupérer un token depuis l'application Flutter (dans les logs de debug)
# Puis tester :

curl -X POST \
  https://chantix.universaltechnologiesafrica.com/api/v1/projects/2/attendances/check-in \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Accept: application/json" \
  -F "check_in_latitude=5.252433" \
  -F "check_in_longitude=-3.944041"
```

Si curl fonctionne mais l'app Flutter non, le problème vient de l'application Flutter.
Si curl ne fonctionne pas non plus, le problème vient du serveur.

## Solution alternative : Désactiver le cache des routes en production

Si le problème persiste, vous pouvez temporairement désactiver le cache des routes :

### Modifier `app/Providers/RouteServiceProvider.php`

Cherchez la méthode `boot()` et commentez ou supprimez toute ligne qui fait du cache des routes.

### Ou modifier `.env`

Ajoutez ou modifiez :
```
ROUTE_CACHE=false
```

Puis :
```bash
php artisan config:clear
```

## Vérification finale

1. **Vérifier les logs Laravel** :
   ```bash
   tail -f storage/logs/laravel.log
   ```
   Puis essayez un check-in depuis l'app. Si vous voyez "Check-in appelé", la requête arrive.

2. **Vérifier les permissions** :
   ```bash
   chmod -R 775 bootstrap/cache storage
   chown -R www-data:www-data bootstrap/cache storage
   ```

3. **Vérifier que le fichier routes/api.php est bien celui utilisé** :
   ```bash
   grep -n "projects/{projectId}" routes/api.php
   ```
   Doit afficher la ligne 47 avec `projects/{projectId}`

## Commandes complètes (copier-coller)

```bash
# 1. Supprimer tous les caches
rm -f bootstrap/cache/routes*.php bootstrap/cache/config.php bootstrap/cache/services.php
rm -rf storage/framework/cache/data/* storage/framework/views/*

# 2. Vider avec artisan
php artisan route:clear && php artisan config:clear && php artisan cache:clear && php artisan view:clear

# 3. Redémarrer PHP-FPM
sudo service php-fpm restart

# 4. Vérifier
php artisan route:list | grep check-in
```

## Si rien ne fonctionne

Vérifiez que le serveur web (Nginx/Apache) n'a pas de cache activé :

```bash
# Nginx
sudo nginx -t
sudo service nginx reload

# Apache
sudo apache2ctl configtest
sudo service apache2 reload
```

