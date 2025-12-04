# Test des Routes

Pour déboguer le problème, suivez ces étapes :

1. **Vérifier les logs Laravel** :
   - Ouvrez `storage/logs/laravel.log`
   - Cherchez les entrées avec "MaterialController::index", "EmployeeController::index", "ProjectController::index"
   - Vérifiez les valeurs de `has_permission`, `is_admin`, `current_role`

2. **Tester directement les URLs** :
   - `/projects` - Devrait afficher la liste des projets
   - `/materials` - Devrait afficher la liste des matériaux
   - `/employees` - Devrait afficher la liste des employés
   - `/companies/1/invitations` - Devrait afficher les invitations (celle qui fonctionne)

3. **Vérifier les permissions dans la base de données** :
   ```sql
   -- Vérifier le rôle de l'utilisateur dans l'entreprise
   SELECT u.id, u.name, u.email, c.name as company_name, r.name as role_name
   FROM users u
   JOIN company_user cu ON u.id = cu.user_id
   JOIN companies c ON cu.company_id = c.id
   JOIN roles r ON cu.role_id = r.id
   WHERE u.email = 'votre_email@example.com';
   ```

4. **Vérifier les permissions du rôle** :
   ```sql
   -- Vérifier les permissions du rôle admin
   SELECT * FROM roles WHERE name = 'admin';
   -- Vérifier les permissions JSON du rôle
   ```

5. **Vider tous les caches** :
   ```bash
   php artisan route:clear
   php artisan config:clear
   php artisan cache:clear
   php artisan view:clear
   php artisan optimize:clear
   ```

6. **Vérifier dans le navigateur** :
   - Ouvrez la console développeur (F12)
   - Allez dans l'onglet Network
   - Cliquez sur un menu (Projets, Matériaux, Employés)
   - Vérifiez la requête HTTP et la réponse
   - Vérifiez s'il y a des redirections (status 302 ou 303)

