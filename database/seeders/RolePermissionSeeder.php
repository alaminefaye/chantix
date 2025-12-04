<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // S'assurer que toutes les permissions ont le guard 'web'
        Permission::whereNull('guard_name')->orWhere('guard_name', '')->update(['guard_name' => 'web']);
        
        // Créer le rôle super_admin avec toutes les permissions
        $superAdminRole = Role::firstOrCreate(
            ['name' => 'super_admin', 'guard_name' => 'web'],
            [
                'display_name' => 'Super Administrateur',
                'description' => 'Accès complet à toutes les fonctionnalités de toutes les entreprises',
            ]
        );
        
        // Créer les rôles Spatie avec le guard 'web'
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin', 'guard_name' => 'web'],
            [
                'display_name' => 'Administrateur',
                'description' => 'Accès complet à toutes les fonctionnalités',
            ]
        );
        $chefChantierRole = Role::firstOrCreate(
            ['name' => 'chef_chantier', 'guard_name' => 'web'],
            [
                'display_name' => 'Chef de Chantier',
                'description' => 'Gestion complète d\'un ou plusieurs chantiers',
            ]
        );
        $ingenieurRole = Role::firstOrCreate(
            ['name' => 'ingenieur', 'guard_name' => 'web'],
            [
                'display_name' => 'Ingénieur',
                'description' => 'Suivi technique et validation des travaux',
            ]
        );
        $ouvrierRole = Role::firstOrCreate(
            ['name' => 'ouvrier', 'guard_name' => 'web'],
            [
                'display_name' => 'Ouvrier',
                'description' => 'Pointage, mise à jour d\'avancement, photos',
            ]
        );
        $comptableRole = Role::firstOrCreate(
            ['name' => 'comptable', 'guard_name' => 'web'],
            [
                'display_name' => 'Comptable',
                'description' => 'Gestion financière, dépenses, budgets',
            ]
        );
        $superviseurRole = Role::firstOrCreate(
            ['name' => 'superviseur', 'guard_name' => 'web'],
            [
                'display_name' => 'Superviseur',
                'description' => 'Vue d\'ensemble, rapports, validation',
            ]
        );

        // Définir le guard name
        $guardName = 'web';
        
        // Récupérer toutes les permissions avec leurs IDs
        $allPermissionIds = Permission::where('guard_name', $guardName)->pluck('id')->toArray();
        
        // Super admin et Admin : toutes les permissions
        // Utiliser directement DB pour insérer dans la table pivot (évite le problème de relation null)
        if (!empty($allPermissionIds)) {
            // Pour super_admin - insérer directement dans role_has_permissions
            foreach ($allPermissionIds as $permissionId) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => $permissionId,
                    'role_id' => $superAdminRole->id,
                    'guard_name' => $guardName,
                ]);
            }
            
            // Pour admin - insérer directement dans role_has_permissions
            foreach ($allPermissionIds as $permissionId) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => $permissionId,
                    'role_id' => $adminRole->id,
                    'guard_name' => $guardName,
                ]);
            }
        }

        // Fonction helper pour assigner des permissions via DB
        $assignPermissions = function($role, $permissionNames) use ($guardName) {
            $permissionIds = Permission::whereIn('name', $permissionNames)
                ->where('guard_name', $guardName)
                ->pluck('id')
                ->toArray();
            
            foreach ($permissionIds as $permissionId) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => $permissionId,
                    'role_id' => $role->id,
                    'guard_name' => $guardName,
                ]);
            }
        };

        // Chef de Chantier
        $assignPermissions($chefChantierRole, [
            'dashboard.view',
            'projects.view',
            'projects.create',
            'projects.update',
            'projects.delete',
            'projects.manage_team',
            'projects.timeline',
            'projects.gallery',
            'progress.view',
            'progress.create',
            'progress.update',
            'tasks.view',
            'tasks.create',
            'tasks.update',
            'tasks.delete',
            'materials.view',
            'materials.create',
            'materials.update',
            'materials.delete',
            'materials.transfer',
            'employees.view',
            'employees.assign',
            'attendances.view',
            'attendances.create',
            'attendances.update',
            'comments.view',
            'comments.create',
            'profile.view',
            'profile.update',
            'notifications.view',
        ]);

        // Ingénieur
        $assignPermissions($ingenieurRole, [
            'dashboard.view',
            'projects.view',
            'projects.timeline',
            'projects.gallery',
            'progress.view',
            'progress.validate',
            'tasks.view',
            'tasks.validate',
            'comments.view',
            'comments.create',
            'profile.view',
            'profile.update',
            'notifications.view',
        ]);

        // Ouvrier
        $assignPermissions($ouvrierRole, [
            'dashboard.view',
            'projects.view',
            'progress.view',
            'progress.create',
            'attendances.view',
            'attendances.create',
            'attendances.checkin',
            'attendances.checkout',
            'comments.view',
            'comments.create',
            'profile.view',
            'profile.update',
            'notifications.view',
        ]);

        // Comptable
        $assignPermissions($comptableRole, [
            'dashboard.view',
            'projects.view',
            'expenses.view',
            'expenses.create',
            'expenses.update',
            'expenses.delete',
            'reports.view',
            'reports.financial',
            'reports.export',
            'comments.view',
            'comments.create',
            'profile.view',
            'profile.update',
            'notifications.view',
        ]);

        // Superviseur
        $assignPermissions($superviseurRole, [
            'dashboard.view',
            'projects.view',
            'projects.validate',
            'projects.timeline',
            'projects.gallery',
            'reports.view',
            'reports.generate',
            'reports.daily',
            'reports.weekly',
            'reports.export',
            'comments.view',
            'comments.create',
            'profile.view',
            'profile.update',
            'notifications.view',
        ]);
    }
}

