# Correction de la Migration Sanctum

## Problème
Laravel essaie de créer la table `personal_access_tokens` plusieurs fois car il y a des migrations en double.

## Solution appliquée
✅ Supprimé les migrations en double :
- `2025_12_04_155403_create_personal_access_tokens_table.php` (supprimé)
- `2025_12_04_155527_create_personal_access_tokens_table.php` (supprimé)

✅ Gardé la migration originale :
- `2025_12_04_143800_create_personal_access_tokens_table.php` (conservée)

## Vérification
La table `personal_access_tokens` existe déjà dans votre base de données (créée par la migration `2025_12_04_143800`).

## Si vous avez encore des erreurs de migration

Si Laravel essaie encore d'exécuter la migration, vous pouvez marquer manuellement la migration comme exécutée :

```bash
php artisan migrate:status
```

Si la migration `2025_12_04_143800_create_personal_access_tokens_table` n'est pas marquée comme exécutée, vous pouvez l'insérer manuellement dans la table `migrations` :

```sql
INSERT INTO migrations (migration, batch) 
VALUES ('2025_12_04_143800_create_personal_access_tokens_table', 
        (SELECT COALESCE(MAX(batch), 0) + 1 FROM (SELECT batch FROM migrations) AS m));
```

Ou simplement ignorer l'erreur car la table existe déjà et fonctionne correctement.

## Prochaines étapes
1. Videz les caches : `php artisan optimize:clear`
2. Testez l'application
3. Le problème de migration ne devrait plus apparaître

