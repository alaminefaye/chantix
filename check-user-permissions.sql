-- Script SQL pour vérifier et corriger les permissions d'un utilisateur
-- Remplacez 'aminefye@gmail.com' par l'email de l'utilisateur à vérifier

-- 1. Vérifier l'utilisateur
SELECT '=== UTILISATEUR ===' AS '';
SELECT 
    id,
    name,
    email,
    is_super_admin,
    current_company_id,
    is_verified
FROM users 
WHERE email = 'aminefye@gmail.com'; -- Remplacez par votre email

-- 2. Vérifier les relations company_user
SELECT '=== RELATIONS COMPANY_USER ===' AS '';
SELECT 
    cu.id,
    cu.company_id,
    c.name AS company_name,
    cu.user_id,
    u.name AS user_name,
    cu.role_id,
    r.name AS role_name,
    r.id AS role_table_id,
    cu.is_active,
    cu.joined_at
FROM company_user cu
JOIN companies c ON cu.company_id = c.id
JOIN users u ON cu.user_id = u.id
LEFT JOIN roles r ON cu.role_id = r.id
WHERE u.email = 'aminefye@gmail.com'; -- Remplacez par votre email

-- 3. Vérifier si le rôle admin existe
SELECT '=== RÔLE ADMIN ===' AS '';
SELECT id, name, display_name FROM roles WHERE name = 'admin';

-- 4. CORRIGER le rôle si nécessaire (décommentez pour exécuter)
-- Assurez-vous d'avoir le bon ID de rôle admin (généralement 1 ou 7 selon les doublons)
/*
UPDATE company_user cu
JOIN users u ON cu.user_id = u.id
SET cu.role_id = (SELECT id FROM roles WHERE name = 'admin' ORDER BY id LIMIT 1)
WHERE u.email = 'aminefye@gmail.com' -- Remplacez par votre email
AND cu.role_id NOT IN (SELECT id FROM roles WHERE name = 'admin');
*/

-- 5. Vérifier les invitations créées par cet utilisateur
SELECT '=== INVITATIONS CRÉÉES ===' AS '';
SELECT 
    i.id,
    i.email,
    i.company_id,
    c.name AS company_name,
    i.invited_by,
    u.name AS inviter_name,
    u.email AS inviter_email,
    i.role_id,
    r.name AS role_name,
    i.status,
    i.created_at
FROM invitations i
JOIN companies c ON i.company_id = c.id
LEFT JOIN users u ON i.invited_by = u.id
LEFT JOIN roles r ON i.role_id = r.id
WHERE u.email = 'aminefye@gmail.com' -- Remplacez par votre email
ORDER BY i.created_at DESC;

-- 6. Vérifier si invited_by est NULL ou incorrect
SELECT '=== INVITATIONS AVEC INVITED_BY NULL ===' AS '';
SELECT 
    i.id,
    i.email,
    i.company_id,
    i.invited_by,
    i.created_at
FROM invitations i
WHERE i.invited_by IS NULL;

-- 7. CORRIGER invited_by si nécessaire (décommentez pour exécuter)
-- Remplacez l'ID de l'invitation et l'email de l'utilisateur
/*
UPDATE invitations i
JOIN users u ON u.email = 'aminefye@gmail.com' -- Remplacez par votre email
SET i.invited_by = u.id
WHERE i.id = 1 -- Remplacez par l'ID de l'invitation
AND i.invited_by IS NULL;
*/

