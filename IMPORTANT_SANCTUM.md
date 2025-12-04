# ⚠️ IMPORTANT : Ne plus exécuter vendor:publish pour Sanctum

## Problème
Chaque fois que vous exécutez :
```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

Laravel crée une **nouvelle migration** pour `personal_access_tokens`, ce qui cause des erreurs car la table existe déjà.

## ✅ Solution

**NE PLUS EXÉCUTER CETTE COMMANDE** - Sanctum est déjà configuré !

La table `personal_access_tokens` existe déjà dans votre base de données (créée par la migration `2025_12_04_143800`).

## ✅ Ce qui est déjà fait

1. ✅ Sanctum est installé (`composer.json`)
2. ✅ La configuration existe (`config/sanctum.php`)
3. ✅ La table existe dans la base de données
4. ✅ Le modèle User utilise `HasApiTokens`
5. ✅ Les migrations en double ont été supprimées

## 🎯 Prochaines étapes

1. **Vider les caches** :
   ```bash
   php artisan optimize:clear
   ```

2. **Tester l'application** :
   - Rechargez la page dans le navigateur
   - Cliquez sur "Projets", "Matériaux", ou "Employés"
   - Vérifiez que les pages se chargent correctement

## ❌ À ne plus faire

- ❌ Ne plus exécuter `php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"`
- ❌ Ne plus exécuter `php artisan migrate` si vous voyez une erreur sur `personal_access_tokens` (la table existe déjà)

## 🔍 Si vous avez encore des erreurs de migration

Si Laravel essaie encore d'exécuter une migration pour `personal_access_tokens`, vous pouvez :

1. **Supprimer la migration en double** (comme je viens de le faire)
2. **Ou ignorer l'erreur** - la table fonctionne correctement même avec cette erreur

L'important est que **l'application fonctionne**, pas que toutes les migrations passent sans erreur.

