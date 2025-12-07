# Guide de Déploiement

## Problème de cache sur le serveur

Si les modifications fonctionnent en local mais pas sur le serveur, c'est généralement dû au cache Laravel.

## Solution rapide

### Option 1: Script de déploiement automatique

```bash
# Sur le serveur, exécuter:
./deploy.sh

# Ou pour la production avec optimisation:
./deploy.sh production
```

### Option 2: Commandes manuelles

```bash
# Se connecter au serveur et aller dans le répertoire du projet
cd /chemin/vers/votre/projet

# Vider tous les caches
php artisan cache:clear-all

# Ou individuellement:
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear
```

### Option 3: Via SSH (si vous avez accès)

```bash
ssh user@votre-serveur.com
cd /chemin/vers/votre/projet
php artisan cache:clear-all
```

## Vérification après déploiement

1. **Vérifier les projets des invitations:**
   ```bash
   php artisan invitations:check-projects
   ```

2. **Vérifier les logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

## Modifications apportées pour résoudre le problème

Le code a été modifié pour **ne plus dépendre du cache Eloquent**:

1. **Vue index**: Utilise maintenant une requête directe sur la table `invitation_project` au lieu de la relation Eloquent
2. **Contrôleur index**: Charge les projets directement depuis la DB
3. **Contrôleur update**: Vérifie directement dans la DB après synchronisation
4. **Contrôleur edit**: Récupère les projets directement depuis la table pivot

Cela garantit que les données sont toujours à jour, même si le cache n'est pas vidé.

## Commandes utiles

- `php artisan cache:clear-all` - Vider tous les caches
- `php artisan invitations:check-projects` - Vérifier les projets des invitations
- `php artisan invitations:check-projects --fix` - Corriger automatiquement les problèmes

## En cas de problème persistant

1. Vérifier les permissions des fichiers:
   ```bash
   chmod -R 775 storage bootstrap/cache
   chown -R www-data:www-data storage bootstrap/cache
   ```

2. Vérifier que la table `invitation_project` existe:
   ```bash
   php artisan migrate:status
   ```

3. Vérifier les logs d'erreur:
   ```bash
   tail -f storage/logs/laravel.log
   ```
