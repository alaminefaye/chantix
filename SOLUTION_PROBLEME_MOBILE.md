# Solution au problème : Voir plusieurs projets au lieu d'un seul

## 🔍 ÉTAPE 1 : Vérifier ce que l'API retourne

Exécutez sur votre serveur :

```bash
bash test_api_mobile.sh
```

**Résultat attendu :** L'API doit retourner **1 seul projet** (celui assigné dans l'invitation)

**Si l'API retourne plus de 1 projet :**
- Le problème est dans le backend
- Vérifiez que les corrections sont bien déployées sur le serveur
- Vérifiez que l'utilisateur a bien seulement 1 projet assigné dans `project_user`

**Si l'API retourne 1 projet :**
- Le problème est dans l'application mobile (cache)
- Passez à l'ÉTAPE 2

---

## 🔧 ÉTAPE 2 : Vérifier les projets assignés dans la base de données

```bash
php artisan tinker --execute="
\$user = App\Models\User::where('email', 'aminefaye@gmail.com')->first();
\$assigned = DB::table('project_user')->where('user_id', \$user->id)->pluck('project_id')->toArray();
echo \"Projets assignés: \" . (empty(\$assigned) ? 'AUCUN' : implode(', ', \$assigned)) . \"\n\";
echo \"Nombre: \" . count(\$assigned) . \"\n\";
"
```

**Si vous voyez plus de 1 projet :**
- Il faut corriger les assignations (voir ÉTAPE 3)

**Si vous voyez 1 projet :**
- Les assignations sont correctes
- Le problème est dans l'app mobile (cache)

---

## 🛠️ ÉTAPE 3 : Corriger les projets assignés (si nécessaire)

Si l'utilisateur a plusieurs projets assignés et vous voulez qu'il n'en ait qu'un seul :

```bash
php artisan tinker --execute="
\$user = App\Models\User::where('email', 'aminefaye@gmail.com')->first();
\$projectId = 1; // Remplacez par l'ID du projet que vous voulez garder

// Supprimer tous les projets
DB::table('project_user')->where('user_id', \$user->id)->delete();

// Assigner seulement le projet souhaité
DB::table('project_user')->insert([
    'user_id' => \$user->id,
    'project_id' => \$projectId,
    'created_at' => now(),
    'updated_at' => now(),
]);

echo \"✅ Projet #\$projectId assigné (tous les autres supprimés)\n\";
"
```

---

## 📱 ÉTAPE 4 : Forcer le rechargement dans l'application mobile

### Option 1 : Pull-to-Refresh
Dans l'application mobile, faites un **glissement vers le bas** (pull-to-refresh) sur l'écran des projets pour forcer le rechargement.

### Option 2 : Déconnexion/Reconnexion
1. Déconnectez-vous de l'application
2. Fermez complètement l'application
3. Rouvrez l'application
4. Reconnectez-vous

### Option 3 : Réinstaller l'application
1. Désinstallez l'application mobile
2. Réinstallez-la
3. Reconnectez-vous

### Option 4 : Vider le cache (si disponible)
Dans les paramètres de l'application, cherchez l'option "Vider le cache" ou "Clear cache"

---

## ✅ Vérification finale

Après avoir fait les corrections :

1. **Vérifiez l'API :**
   ```bash
   bash test_api_mobile.sh
   ```
   Doit retourner **1 projet**

2. **Vérifiez dans l'app mobile :**
   - Ouvrez l'application
   - Allez sur l'écran des projets
   - Vous devriez voir **1 seul projet**

---

## 📝 Modifications apportées au code

### Backend (Laravel)
1. ✅ Méthode `accessibleByUser` corrigée : retourne seulement les projets assignés
2. ✅ Création directe d'utilisateur : supprime les anciens projets avant d'assigner
3. ✅ Modification d'invitation : synchronise correctement les projets
4. ✅ Acceptation d'invitation : supprime les anciens projets

### Frontend (Flutter)
1. ✅ Méthode `reloadProjects()` ajoutée pour forcer le rechargement
2. ✅ Pull-to-refresh utilise maintenant `reloadProjects()` au lieu de `loadProjects()`

---

## 🐛 Si le problème persiste

1. Vérifiez les logs Laravel :
   ```bash
   tail -f storage/logs/laravel.log | grep -E "API Projects|accessibleByUser"
   ```

2. Vérifiez les logs de l'application mobile (dans la console de développement)

3. Vérifiez que l'URL de l'API est correcte dans l'application mobile

4. Vérifiez que le token d'authentification est valide

---

## 📞 Support

Si le problème persiste après avoir suivi toutes ces étapes, partagez :
- Le résultat de `test_api_mobile.sh`
- Le nombre de projets assignés dans la base de données
- Les logs Laravel récents
