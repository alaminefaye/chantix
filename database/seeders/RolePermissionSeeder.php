<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\Role as OldRole;

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
            ['name' => 'super_admin', 'guard_name' => 'web']
        );
        // Le super admin a toutes les permissions
        $superAdminRole->givePermissionTo(Permission::where('guard_name', 'web')->get());
        
        // Créer les rôles Spatie avec le guard 'web'
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin', 'guard_name' => 'web']
        );
        $chefChantierRole = Role::firstOrCreate(
            ['name' => 'chef_chantier', 'guard_name' => 'web']
        );
        $ingenieurRole = Role::firstOrCreate(
            ['name' => 'ingenieur', 'guard_name' => 'web']
        );
        $ouvrierRole = Role::firstOrCreate(
            ['name' => 'ouvrier', 'guard_name' => 'web']
        );
        $comptableRole = Role::firstOrCreate(
            ['name' => 'comptable', 'guard_name' => 'web']
        );
        $superviseurRole = Role::firstOrCreate(
            ['name' => 'superviseur', 'guard_name' => 'web']
        );

        // Créer aussi les rôles dans l'ancienne table pour la compatibilité
        OldRole::firstOrCreate(['name' => 'admin'], [
            'display_name' => 'Administrateur',
            'description' => 'Accès complet à toutes les fonctionnalités',
        ]);
        OldRole::firstOrCreate(['name' => 'chef_chantier'], [
            'display_name' => 'Chef de Chantier',
            'description' => 'Gestion complète d\'un ou plusieurs chantiers',
        ]);
        OldRole::firstOrCreate(['name' => 'ingenieur'], [
            'display_name' => 'Ingénieur',
            'description' => 'Suivi technique et validation des travaux',
        ]);
        OldRole::firstOrCreate(['name' => 'ouvrier'], [
            'display_name' => 'Ouvrier',
            'description' => 'Pointage, mise à jour d\'avancement, photos',
        ]);
        OldRole::firstOrCreate(['name' => 'comptable'], [
            'display_name' => 'Comptable',
            'description' => 'Gestion financière, dépenses, budgets',
        ]);
        OldRole::firstOrCreate(['name' => 'superviseur'], [
            'display_name' => 'Superviseur',
            'description' => 'Vue d\'ensemble, rapports, validation',
        ]);

        // Admin : toutes les permissions (avec le guard 'web')
        $adminRole->givePermissionTo(Permission::where('guard_name', 'web')->get());

        // Chef de Chantier
        $chefChantierRole->givePermissionTo([
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
        $ingenieurRole->givePermissionTo([
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
        $ouvrierRole->givePermissionTo([
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
        $comptableRole->givePermissionTo([
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
        $superviseurRole->givePermissionTo([
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

