# Instructions de Débogage

## Étape 1 : Vérifier dans le navigateur

1. **Ouvrez la console développeur** (F12)
2. **Allez dans l'onglet Console** (pas Network)
3. **Cliquez sur "Projets" dans le sidebar**
4. **Regardez s'il y a des erreurs JavaScript** dans la console

## Étape 2 : Vérifier les requêtes réseau

1. **Allez dans l'onglet Network** (comme sur votre capture d'écran)
2. **Cochez "Preserve log"** pour garder l'historique
3. **Cliquez sur "Projets" dans le sidebar**
4. **Cherchez une requête vers `/projects`** dans la liste
5. **Cliquez sur cette requête** et regardez :
   - **Status Code** : Est-ce 200, 302, 403, 404 ?
   - **Response** : Que contient la réponse ?
   - **Headers** : Y a-t-il une redirection (Location header) ?

## Étape 3 : Tester directement les URLs

Dans la barre d'adresse, essayez d'accéder directement à :
- `http://chantix.test/projects`
- `http://chantix.test/materials`
- `http://chantix.test/employees`

## Étape 4 : Vérifier les logs Laravel

1. Ouvrez le fichier `storage/logs/laravel.log`
2. Cherchez les entrées récentes avec :
   - `ProjectController::index`
   - `MaterialController::index`
   - `EmployeeController::index`
3. Vérifiez les valeurs de `has_permission`, `is_admin`, `current_role`

## Étape 5 : Vérifier les permissions dans la base de données

Exécutez cette requête SQL pour vérifier le rôle de l'utilisateur :

```sql
SELECT 
    u.id,
    u.name,
    u.email,
    u.current_company_id,
    c.name as company_name,
    r.name as role_name,
    r.id as role_id
FROM users u
LEFT JOIN company_user cu ON u.id = cu.user_id AND cu.company_id = u.current_company_id
LEFT JOIN companies c ON cu.company_id = c.id
LEFT JOIN roles r ON cu.role_id = r.id
WHERE u.email = 'votre_email@example.com';
```

Remplacez `'votre_email@example.com'` par votre email.

## Questions à répondre

1. **Les liens "Projets", "Matériaux", "Employés" apparaissent-ils dans le sidebar ?**
2. **Quand vous cliquez dessus, que se passe-t-il ?**
   - L'URL change-t-elle dans la barre d'adresse ?
   - La page reste-t-elle sur le dashboard ?
   - Y a-t-il une erreur dans la console ?
3. **Dans l'onglet Network, voyez-vous une requête vers `/projects`, `/materials`, ou `/employees` ?**
4. **Quel est le status code de cette requête ?** (200, 302, 403, 404, etc.)

