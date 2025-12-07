# Vérification sur le Serveur

## 🔍 Diagnostic du problème

Si les projets multiples ne s'affichent pas sur le serveur, suivez ces étapes :

### 1. Vérifier les données dans la base de données

```bash
# Se connecter au serveur
ssh user@votre-serveur.com
cd /chemin/vers/votre/projet

# Ouvrir tinker
php artisan tinker

# Dans tinker, vérifier une invitation spécifique
$invitation = App\Models\Invitation::find(1); // Remplacez 1 par l'ID de l'invitation
$projectIds = DB::table('invitation_project')->where('invitation_id', $invitation->id)->pluck('project_id');
echo "IDs des projets dans la table pivot: " . $projectIds->implode(', ');
$projects = $invitation->getProjectsDirectly();
echo "Nombre de projets récupérés: " . $projects->count();
$projects->each(function($p) { echo $p->name . "\n"; });
```

### 2. Vérifier les logs

```bash
# Voir les logs en temps réel
tail -f storage/logs/laravel.log | grep "Invitation\|projet"

# Ou chercher spécifiquement
grep "Affichage projets invitation" storage/logs/laravel.log
```

### 3. Vérifier que la table existe et contient les données

```bash
php artisan tinker

# Vérifier la table
DB::table('invitation_project')->get();

# Vérifier une invitation spécifique
DB::table('invitation_project')->where('invitation_id', 1)->get();
```

### 4. Tester la méthode directement

```bash
php artisan tinker

$invitation = App\Models\Invitation::find(1);
$projects = $invitation->getProjectsDirectly();
dd($projects->toArray());
```

### 5. Vérifier les permissions et le cache

```bash
# Vider TOUS les caches
./deploy.sh

# Ou manuellement
php artisan cache:clear-all
php -r "if(function_exists('opcache_reset')) opcache_reset();"
```

### 6. Vérifier la configuration PHP

```bash
# Vérifier OPcache
php -i | grep opcache

# Vérifier si OPcache est actif
php -r "echo function_exists('opcache_reset') ? 'OPcache actif' : 'OPcache inactif';"
```

## 🐛 Problèmes courants

### Problème 1: La table invitation_project n'existe pas

```bash
php artisan migrate
php artisan migrate:status
```

### Problème 2: Les données ne sont pas dans la table pivot

Vérifier que lors de la modification, les projets sont bien sauvegardés :

```bash
php artisan tinker
$invitation = App\Models\Invitation::find(1);
$invitation->projects()->sync([1, 2]); // Remplacez par les IDs des projets
DB::table('invitation_project')->where('invitation_id', 1)->get();
```

### Problème 3: Le cache OPcache bloque les modifications

```bash
# Vider OPcache
php -r "if(function_exists('opcache_reset')) opcache_reset();"

# Redémarrer PHP-FPM
sudo systemctl restart php8.1-fpm
```

### Problème 4: Les fichiers ne sont pas à jour sur le serveur

```bash
# Vérifier la date de modification des fichiers
ls -la app/Models/Invitation.php
ls -la resources/views/invitations/index.blade.php

# Si nécessaire, re-télécharger les fichiers
git pull origin main
# ou re-uploader les fichiers modifiés
```

## ✅ Solution de contournement temporaire

Si le problème persiste, vous pouvez forcer l'affichage avec une requête SQL directe dans la vue :

```php
@php
  $projectIds = DB::table('invitation_project')
    ->where('invitation_id', $invitation->id)
    ->pluck('project_id')
    ->toArray();
  
  $projects = DB::table('projects')
    ->whereIn('id', $projectIds)
    ->get();
@endphp

@foreach($projects as $project)
  <span class="badge bg-primary">{{ $project->name }}</span>
@endforeach
```

## 📞 Informations à collecter pour le debug

Si le problème persiste, collectez ces informations :

1. **Logs Laravel** : `storage/logs/laravel.log`
2. **Résultat de tinker** : `$invitation->getProjectsDirectly()`
3. **Données de la table pivot** : `DB::table('invitation_project')->get()`
4. **Version PHP** : `php -v`
5. **Configuration OPcache** : `php -i | grep opcache`
