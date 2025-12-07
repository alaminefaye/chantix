# Guide de Déploiement - Serveur

## ⚠️ Problème de cache sur le serveur

Si les modifications fonctionnent en local mais pas sur le serveur, c'est généralement dû au cache Laravel, OPcache ou APCu.

## 🚀 Solution rapide (RECOMMANDÉ)

### Option 1: Script de déploiement automatique (LE PLUS SIMPLE)

```bash
# Sur le serveur, exécuter:
./deploy.sh

# Ou pour la production avec optimisation:
./deploy.sh production
```

Ce script fait automatiquement:
- ✅ Vide tous les caches Laravel
- ✅ Vide OPcache (cache PHP)
- ✅ Vide APCu (si disponible)
- ✅ Nettoie les fichiers compilés
- ✅ Vérifie les permissions

### Option 2: Commande Artisan

```bash
# Sur le serveur:
php artisan cache:clear-all
```

Cette commande vide:
- Cache Laravel
- Configuration
- Routes
- Vues
- Événements
- OPcache
- APCu
- Fichiers compilés

### Option 3: Commandes manuelles

```bash
# Se connecter au serveur
ssh user@votre-serveur.com
cd /chemin/vers/votre/projet

# Vider tous les caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear

# Vider OPcache (cache PHP)
php -r "if(function_exists('opcache_reset')) opcache_reset();"

# Nettoyer les fichiers compilés
rm -rf bootstrap/cache/*.php
rm -rf storage/framework/cache/data/*
rm -rf storage/framework/views/*.php
```

## 🔧 Modifications apportées

Le code a été **complètement refactorisé** pour ne plus dépendre du cache:

### 1. Nouvelle méthode dans le modèle Invitation

Une méthode `getProjectsDirectly()` a été ajoutée qui:
- ✅ Fait une requête directe sur la table `invitation_project`
- ✅ Ne dépend pas du cache Eloquent
- ✅ Fonctionne même si le cache n'est pas vidé

### 2. Toutes les vues utilisent maintenant cette méthode

- `index.blade.php` → Utilise `$invitation->getProjectsDirectly()`
- `show.blade.php` → Utilise `$invitation->getProjectsDirectly()`

### 3. Tous les contrôleurs utilisent cette méthode

- `index()` → Charge les projets avec `getProjectsDirectly()`
- `edit()` → Récupère les projets avec `getProjectsDirectly()`
- `update()` → Utilise `getProjectsDirectly()` avant et après synchronisation
- `show()` → Charge les projets avec `getProjectsDirectly()`
- `accept()` → Utilise `getProjectsDirectly()` pour associer les projets

## ✅ Vérification après déploiement

1. **Vérifier les projets des invitations:**
   ```bash
   php artisan invitations:check-projects
   ```

2. **Vérifier les logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

3. **Tester dans le navigateur:**
   - Modifier une invitation avec plusieurs projets
   - Vérifier que tous les projets s'affichent dans la liste

## 📋 Commandes utiles

- `./deploy.sh` - Script de déploiement complet
- `php artisan cache:clear-all` - Vider tous les caches
- `php artisan invitations:check-projects` - Vérifier les projets
- `php artisan invitations:check-projects --fix` - Corriger les problèmes

## 🔍 En cas de problème persistant

### 1. Vérifier les permissions

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 2. Vérifier que la table existe

```bash
php artisan migrate:status
php artisan migrate
```

### 3. Vérifier les logs

```bash
tail -f storage/logs/laravel.log
```

### 4. Redémarrer le serveur web (si possible)

```bash
# Apache
sudo systemctl restart apache2

# Nginx + PHP-FPM
sudo systemctl restart php8.1-fpm
sudo systemctl restart nginx
```

### 5. Vérifier OPcache dans php.ini

Assurez-vous que OPcache est configuré correctement. Si nécessaire, redémarrez PHP-FPM.

## 🎯 Avantages de cette solution

1. **Ne dépend plus du cache**: Le code utilise des requêtes directes
2. **Fonctionne même si le cache n'est pas vidé**: Les données viennent directement de la DB
3. **Plus rapide**: Moins de dépendances au cache
4. **Plus fiable**: Moins de problèmes de synchronisation

## 📝 Notes importantes

- Le script `deploy.sh` doit être exécutable: `chmod +x deploy.sh`
- Exécutez le script après chaque déploiement
- En production, utilisez `./deploy.sh production` pour optimiser les caches
