# 🚀 Guide de Déploiement - Chantix

Ce guide explique comment déployer les modifications sur le serveur et résoudre les problèmes de permissions.

## 📋 Étapes de Déploiement

### 1. Uploader les fichiers modifiés

Assurez-vous d'avoir uploadé tous les fichiers modifiés sur le serveur :
- `app/Models/User.php`
- `app/Http/Controllers/InvitationController.php`
- Tous les autres fichiers modifiés

### 2. Exécuter le script de déploiement

Sur le serveur, exécutez :

```bash
cd /chemin/vers/chantix
chmod +x deploy-server.sh
./deploy-server.sh
```

Ce script va :
- ✅ Mettre à jour les dépendances Composer
- ✅ Vider tous les caches Laravel
- ✅ Exécuter les migrations
- ✅ Vérifier les seeders (rôles)
- ✅ Optimiser pour la production
- ✅ Vider le cache OPcache

### 3. Vérifier l'état du serveur

Pour diagnostiquer les problèmes, exécutez :

```bash
./check-server-status.sh
```

Ce script va :
- ✅ Vérifier la version PHP et Composer
- ✅ Vérifier l'état des migrations
- ✅ Vérifier les rôles dans la base de données
- ✅ Vérifier un utilisateur spécifique
- ✅ Vérifier les invitations
- ✅ Vérifier les caches
- ✅ Afficher les dernières erreurs

### 4. Vérifier la base de données

Si le problème persiste, vérifiez directement dans la base de données :

```bash
mysql -u votre_user -p votre_database < check-database.sql
```

Ou exécutez les requêtes SQL manuellement dans votre outil de gestion de base de données.

## 🔍 Diagnostic des Problèmes de Permissions

### Problème : "403 Accès non autorisé" pour les invitations

#### Vérification 1 : L'utilisateur a-t-il le rôle admin ?

```sql
SELECT 
    u.email,
    c.name AS company_name,
    r.name AS role_name
FROM users u
JOIN company_user cu ON u.id = cu.user_id
JOIN companies c ON cu.company_id = c.id
JOIN roles r ON cu.role_id = r.id
WHERE u.email = 'votre_email@example.com'
AND cu.is_active = 1;
```

**Solution** : Si le rôle n'est pas "admin", vous devez :
1. Vérifier que le seeder des rôles a été exécuté : `php artisan db:seed --class=RoleSeeder`
2. Mettre à jour manuellement le rôle dans `company_user` :

```sql
UPDATE company_user cu
JOIN users u ON cu.user_id = u.id
JOIN roles r ON cu.role_id = r.id
SET cu.role_id = (SELECT id FROM roles WHERE name = 'admin' LIMIT 1)
WHERE u.email = 'votre_email@example.com'
AND r.name != 'admin';
```

#### Vérification 2 : L'invitation a-t-elle un `invited_by` correct ?

```sql
SELECT 
    i.id,
    i.email,
    i.invited_by,
    u.name AS inviter_name,
    u.email AS inviter_email
FROM invitations i
LEFT JOIN users u ON i.invited_by = u.id
WHERE i.id = 1; -- Remplacez par l'ID de l'invitation
```

**Solution** : Si `invited_by` est NULL ou incorrect, mettez à jour :

```sql
UPDATE invitations i
JOIN users u ON u.email = 'votre_email@example.com'
SET i.invited_by = u.id
WHERE i.id = 1; -- Remplacez par l'ID de l'invitation
```

#### Vérification 3 : Les caches sont-ils vidés ?

```bash
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan view:clear
```

#### Vérification 4 : Les logs Laravel

Consultez les logs pour voir les détails de l'erreur :

```bash
tail -f storage/logs/laravel.log
```

Les logs contiennent maintenant des informations détaillées sur :
- L'ID de l'utilisateur
- L'ID de l'invitation
- Si l'utilisateur est admin
- Si l'utilisateur est le créateur
- Le résultat de `hasRoleInCompany()`

## 🛠️ Commandes Manuelles

Si les scripts ne fonctionnent pas, exécutez ces commandes manuellement :

```bash
# 1. Mettre à jour Composer
composer install --no-dev --optimize-autoloader
composer dump-autoload --optimize

# 2. Vider les caches
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan view:clear

# 3. Migrations
php artisan migrate --force

# 4. Seeders
php artisan db:seed --class=RoleSeeder --force

# 5. Optimiser pour la production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Vider OPcache (si disponible)
php -r "if (function_exists('opcache_reset')) { opcache_reset(); }"
```

## 📝 Notes Importantes

1. **Cache OPcache** : Si votre serveur utilise OPcache, vous devez le vider après chaque déploiement
2. **Permissions** : Assurez-vous que `storage/` et `bootstrap/cache/` sont accessibles en écriture
3. **Base de données** : Vérifiez que les migrations sont à jour avec `php artisan migrate:status`
4. **Rôles** : Les rôles doivent être créés via le seeder `RoleSeeder`

## 🆘 En cas de problème persistant

1. Vérifiez les logs : `storage/logs/laravel.log`
2. Vérifiez la base de données avec `check-database.sql`
3. Vérifiez que tous les fichiers ont été uploadés
4. Vérifiez que les permissions des fichiers sont correctes
5. Contactez le support avec les logs et les résultats des vérifications

