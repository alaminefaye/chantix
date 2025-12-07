# Instructions de Déploiement sur le Serveur

## 🎯 Étapes à suivre sur le serveur

### 1. Se connecter au serveur

```bash
ssh user@votre-serveur.com
cd /chemin/vers/votre/projet/chantix
```

### 2. Télécharger les modifications (si via Git)

```bash
git pull origin main
# ou
git pull origin master
```

### 3. Exécuter le script de déploiement

```bash
# Rendre le script exécutable (première fois seulement)
chmod +x deploy.sh

# Exécuter le script
./deploy.sh
```

### 4. Vérifier que tout fonctionne

```bash
# Vérifier les projets des invitations
php artisan invitations:check-projects

# Vérifier les logs
tail -n 50 storage/logs/laravel.log
```

## 🔧 Si le script ne fonctionne pas

### Méthode manuelle complète

```bash
# 1. Vider tous les caches Laravel
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear

# 2. Vider OPcache (cache PHP)
php -r "if(function_exists('opcache_reset')) { opcache_reset(); echo 'OPcache vidé\n'; } else { echo 'OPcache non disponible\n'; }"

# 3. Vider APCu (si disponible)
php -r "if(function_exists('apcu_clear_cache')) { apcu_clear_cache(); echo 'APCu vidé\n'; } else { echo 'APCu non disponible\n'; }"

# 4. Nettoyer les fichiers compilés
rm -rf bootstrap/cache/*.php
rm -rf storage/framework/cache/data/*
rm -rf storage/framework/views/*.php

# 5. Vérifier les permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# 6. Redémarrer PHP-FPM (si nécessaire)
sudo systemctl restart php8.1-fpm
# ou
sudo service php8.1-fpm restart
```

## ✅ Test final

1. Ouvrir le navigateur et aller sur votre site
2. Se connecter en tant qu'administrateur
3. Aller dans "Invitations"
4. Modifier une invitation et sélectionner plusieurs projets
5. Enregistrer
6. Vérifier que tous les projets s'affichent dans la liste

## 🆘 En cas d'erreur

### Erreur: "Permission denied" sur deploy.sh

```bash
chmod +x deploy.sh
```

### Erreur: "Command not found: php"

Vérifier le chemin PHP:
```bash
which php
# Utiliser le chemin complet, ex: /usr/bin/php artisan cache:clear
```

### Erreur: "Artisan not found"

Vérifier que vous êtes dans le bon répertoire:
```bash
pwd
ls -la artisan
```

### Les projets ne s'affichent toujours pas

1. Vérifier les logs:
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. Vérifier la table dans la base de données:
   ```bash
   php artisan tinker
   # Puis dans tinker:
   DB::table('invitation_project')->get();
   ```

3. Vérifier une invitation spécifique:
   ```bash
   php artisan tinker
   # Puis:
   $invitation = App\Models\Invitation::find(1);
   $invitation->getProjectsDirectly();
   ```

## 📞 Support

Si le problème persiste après avoir suivi toutes ces étapes, vérifier:
- Les logs Laravel: `storage/logs/laravel.log`
- Les logs PHP: `/var/log/php-fpm/error.log` ou `/var/log/apache2/error.log`
- Les permissions des fichiers
- La configuration de la base de données
