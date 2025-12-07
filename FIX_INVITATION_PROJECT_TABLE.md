# Correction du problème : Table 'invitation_project' n'existe pas

## Problème
L'erreur suivante apparaît sur le serveur :
```
SQLSTATE[42S02]: Base table or view not found: 1146 Table 'sema9615_chantix.invitation_project' doesn't exist
```

Cette erreur se produit lors de l'accès à la page d'édition d'une invitation (`/companies/1/invitations/1/edit`) car le code essaie d'accéder à la relation `$invitation->projects` qui nécessite la table pivot `invitation_project`.

## Solution

### 1. Exécuter la migration sur le serveur

Connectez-vous à votre serveur et exécutez la migration :

```bash
cd /chemin/vers/votre/projet/chantix

# Exécuter la migration
php artisan migrate

# Ou si vous voulez exécuter uniquement cette migration spécifique
php artisan migrate --path=database/migrations/2025_12_07_003227_create_invitation_project_table.php
```

### 2. Vérifier que la table a été créée

```bash
# Vérifier via tinker
php artisan tinker
>>> Schema::hasTable('invitation_project');
# Devrait retourner true

# Ou vérifier directement dans MySQL
mysql -u votre_user -p
USE sema9615_chantix;
SHOW TABLES LIKE 'invitation_project';
DESCRIBE invitation_project;
```

### 3. Vider les caches (recommandé après migration)

```bash
php artisan route:clear
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### 4. Vérifier la structure de la table

La table `invitation_project` doit avoir la structure suivante :
- `id` (primary key)
- `invitation_id` (foreign key vers `invitations`)
- `project_id` (foreign key vers `projects`)
- `created_at`
- `updated_at`
- Contrainte unique sur `(invitation_id, project_id)`

## Migration concernée

**Fichier :** `database/migrations/2025_12_07_003227_create_invitation_project_table.php`

Cette migration crée la table pivot nécessaire pour la relation many-to-many entre `invitations` et `projects`.

## Vérification après correction

1. Accédez à la page d'édition d'une invitation : `/companies/1/invitations/1/edit`
2. La page devrait se charger sans erreur
3. Le champ de sélection des projets devrait fonctionner correctement

## Notes importantes

- Cette table est utilisée par la relation `projects()` dans le modèle `Invitation`
- Les données existantes dans la table `invitations` ne seront pas affectées
- Si des invitations avaient déjà des projets associés via l'ancienne colonne `project_id`, vous devrez peut-être migrer ces données manuellement si nécessaire



