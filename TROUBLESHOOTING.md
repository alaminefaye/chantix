# 🔧 Guide de Dépannage - Problème d'Accès aux Invitations

## 🎯 Problème
En local, tout fonctionne. Sur le serveur, vous obtenez une erreur 403 même si vous êtes admin et créateur de l'invitation.

## 📋 Checklist de Vérification

### Étape 1 : Vérifier que les fichiers sont bien uploadés

```bash
./verify-server-files.sh
```

Ce script vérifie :
- ✅ Que les fichiers modifiés existent
- ✅ Que les modifications sont présentes dans les fichiers
- ✅ Les permissions des logs
- ✅ La connexion à la base de données

### Étape 2 : Corriger les permissions des logs

```bash
./fix-logs-permissions.sh
```

Ce script :
- ✅ Crée le répertoire `storage/logs` s'il n'existe pas
- ✅ Crée le fichier `laravel.log` s'il n'existe pas
- ✅ Corrige les permissions (775)
- ✅ Teste l'écriture dans les logs

### Étape 3 : Vider TOUS les caches

```bash
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan view:clear
```

**IMPORTANT** : Après avoir vidé les caches, recréez-les :

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Étape 4 : Vérifier la base de données

Exécutez ce script pour tester l'accès :

```bash
php test-invitation-access.php
```

Ou manuellement dans MySQL :

```sql
-- Vérifier votre utilisateur
SELECT id, name, email FROM users WHERE email = 'aminefye@gmail.com';

-- Vérifier vos invitations
SELECT id, email, company_id, invited_by, status 
FROM invitations 
WHERE invited_by = (SELECT id FROM users WHERE email = 'aminefye@gmail.com');

-- Vérifier votre rôle dans company_user
SELECT 
    cu.*,
    r.name AS role_name
FROM company_user cu
JOIN users u ON cu.user_id = u.id
JOIN roles r ON cu.role_id = r.id
WHERE u.email = 'aminefye@gmail.com';
```

### Étape 5 : Vérifier les logs après une tentative d'accès

1. Essayez d'accéder à `/companies/1/invitations/1/edit` sur le serveur
2. Immédiatement après, consultez les logs :

```bash
tail -50 storage/logs/laravel.log | grep -A 5 -B 5 "EDIT INVITATION\|SHOW INVITATION"
```

Vous devriez voir des logs comme :
```
[2025-12-04 20:47:00] local.INFO: === EDIT INVITATION CALLED ===
[2025-12-04 20:47:00] local.INFO: Edit: Checking if user is creator
[2025-12-04 20:47:00] local.INFO: Edit: User is creator, allowing access
```

### Étape 6 : Si les logs sont toujours vides

#### Vérifier que Laravel peut écrire dans les logs

```bash
php -r "
\$logFile = __DIR__ . '/storage/logs/laravel.log';
\$test = '[' . date('Y-m-d H:i:s') . '] TEST' . PHP_EOL;
file_put_contents(\$logFile, \$test, FILE_APPEND);
echo 'Test écrit dans: ' . \$logFile . PHP_EOL;
"
```

#### Vérifier les permissions

```bash
ls -la storage/logs/
```

Le fichier `laravel.log` doit être accessible en écriture (permissions 664 ou 775).

#### Vérifier le propriétaire

```bash
ls -la storage/logs/laravel.log
```

Le propriétaire doit être l'utilisateur web (généralement `www-data` ou `apache`).

### Étape 7 : Vérifier que le code est bien exécuté

Ajoutez temporairement ce code au début de `edit()` pour forcer une erreur visible :

```php
public function edit(Company $company, Invitation $invitation)
{
    // TEST TEMPORAIRE - À RETIRER APRÈS
    if (request()->has('test')) {
        return response()->json([
            'user_id' => Auth::id(),
            'invitation_id' => $invitation->id,
            'invited_by' => $invitation->invited_by,
            'is_creator' => ($invitation->invited_by == Auth::id()),
        ]);
    }
    
    // ... reste du code
}
```

Puis testez : `/companies/1/invitations/1/edit?test=1`

## 🔍 Diagnostic Avancé

### Vérifier la version des fichiers sur le serveur

```bash
# Vérifier la date de modification
stat app/Http/Controllers/InvitationController.php
stat app/Models/User.php

# Vérifier le contenu (chercher "PRIORITÉ 1")
grep -n "PRIORITÉ 1" app/Http/Controllers/InvitationController.php
```

### Vérifier que les routes sont bien chargées

```bash
php artisan route:list | grep invitations
```

Vous devriez voir :
```
GET|HEAD  companies/{company}/invitations/{invitation}/edit
GET|HEAD  companies/{company}/invitations/{invitation}
```

### Vérifier OPcache (si activé)

```bash
php -r "if (function_exists('opcache_reset')) { opcache_reset(); echo 'OPcache vidé'; } else { echo 'OPcache non disponible'; }"
```

## 🚨 Solutions Courantes

### Problème 1 : Les fichiers ne sont pas uploadés
**Solution** : Re-uploader les fichiers `InvitationController.php` et `User.php`

### Problème 2 : Le cache n'est pas vidé
**Solution** : Exécuter `php artisan optimize:clear` puis recréer les caches

### Problème 3 : Les logs ne sont pas écrits
**Solution** : Exécuter `./fix-logs-permissions.sh`

### Problème 4 : `invited_by` est NULL ou incorrect dans la base de données
**Solution** : Corriger avec :
```sql
UPDATE invitations i
JOIN users u ON u.email = 'aminefye@gmail.com'
SET i.invited_by = u.id
WHERE i.invited_by IS NULL OR i.invited_by != u.id;
```

### Problème 5 : Le rôle admin n'est pas correct
**Solution** : Vérifier et corriger :
```sql
UPDATE company_user cu
JOIN users u ON cu.user_id = u.id
SET cu.role_id = (SELECT id FROM roles WHERE name = 'admin' ORDER BY id LIMIT 1)
WHERE u.email = 'aminefye@gmail.com';
```

## 📞 Si Rien Ne Fonctionne

1. Exécutez tous les scripts de diagnostic
2. Copiez les résultats
3. Vérifiez les logs avec `tail -100 storage/logs/laravel.log`
4. Vérifiez la base de données avec les requêtes SQL ci-dessus
5. Partagez les résultats pour un diagnostic plus approfondi

