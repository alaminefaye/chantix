-- Script SQL pour vérifier l'état de la base de données
-- À exécuter sur le serveur pour diagnostiquer les problèmes de permissions

-- 1. Vérifier les rôles
SELECT '=== RÔLES ===' AS '';
SELECT id, name, display_name FROM roles ORDER BY id;

-- 2. Vérifier un utilisateur spécifique (remplacez l'email)
SELECT '=== UTILISATEUR ===' AS '';
SELECT id, name, email, is_super_admin, current_company_id 
FROM users 
WHERE email = 'aminefye@gmail.com'; -- Remplacez par votre email

-- 3. Vérifier les relations company_user pour cet utilisateur
SELECT '=== RELATIONS COMPANY_USER ===' AS '';
SELECT 
    cu.id,
    cu.company_id,
    c.name AS company_name,
    cu.user_id,
    u.name AS user_name,
    cu.role_id,
    r.name AS role_name,
    cu.is_active,
    cu.joined_at
FROM company_user cu
JOIN companies c ON cu.company_id = c.id
JOIN users u ON cu.user_id = u.id
LEFT JOIN roles r ON cu.role_id = r.id
WHERE u.email = 'aminefye@gmail.com'; -- Remplacez par votre email

-- 4. Vérifier les invitations créées par cet utilisateur
SELECT '=== INVITATIONS CRÉÉES ===' AS '';
SELECT 
    i.id,
    i.email,
    i.company_id,
    c.name AS company_name,
    i.invited_by,
    u.name AS inviter_name,
    i.role_id,
    r.name AS role_name,
    i.status,
    i.created_at
FROM invitations i
JOIN companies c ON i.company_id = c.id
JOIN users u ON i.invited_by = u.id
LEFT JOIN roles r ON i.role_id = r.id
WHERE u.email = 'aminefye@gmail.com' -- Remplacez par votre email
ORDER BY i.created_at DESC
LIMIT 10;

-- 5. Vérifier si l'utilisateur est admin dans company_user
SELECT '=== VÉRIFICATION ADMIN ===' AS '';
SELECT 
    u.id AS user_id,
    u.name AS user_name,
    u.email,
    c.id AS company_id,
    c.name AS company_name,
    r.name AS role_name,
    CASE 
        WHEN r.name = 'admin' THEN 'OUI'
        ELSE 'NON'
    END AS is_admin
FROM users u
JOIN company_user cu ON u.id = cu.user_id
JOIN companies c ON cu.company_id = c.id
JOIN roles r ON cu.role_id = r.id
WHERE u.email = 'aminefye@gmail.com' -- Remplacez par votre email
AND cu.is_active = 1;

