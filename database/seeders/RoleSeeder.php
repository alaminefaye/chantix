<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'admin',
                'display_name' => 'Administrateur',
                'description' => 'Accès complet à toutes les fonctionnalités',
                'permissions' => ['*'], // Toutes les permissions
            ],
            [
                'name' => 'chef_chantier',
                'display_name' => 'Chef de Chantier',
                'description' => 'Gestion complète d\'un ou plusieurs chantiers',
                'permissions' => [
                    'projects.view',
                    'projects.create',
                    'projects.update',
                    'projects.delete',
                    'projects.manage_team',
                    'progress.update',
                    'tasks.manage',
                    'materials.manage',
                ],
            ],
            [
                'name' => 'ingenieur',
                'display_name' => 'Ingénieur',
                'description' => 'Suivi technique et validation des travaux',
                'permissions' => [
                    'projects.view',
                    'progress.view',
                    'progress.validate',
                    'tasks.view',
                    'tasks.validate',
                ],
            ],
            [
                'name' => 'ouvrier',
                'display_name' => 'Ouvrier',
                'description' => 'Pointage, mise à jour d\'avancement, photos',
                'permissions' => [
                    'projects.view',
                    'progress.update',
                    'checkin.create',
                    'photos.upload',
                ],
            ],
            [
                'name' => 'comptable',
                'display_name' => 'Comptable',
                'description' => 'Gestion financière, dépenses, budgets',
                'permissions' => [
                    'projects.view',
                    'expenses.view',
                    'expenses.create',
                    'expenses.update',
                    'expenses.delete',
                    'budget.view',
                    'budget.manage',
                    'reports.financial',
                ],
            ],
            [
                'name' => 'superviseur',
                'display_name' => 'Superviseur',
                'description' => 'Vue d\'ensemble, rapports, validation',
                'permissions' => [
                    'projects.view',
                    'projects.validate',
                    'reports.view',
                    'reports.generate',
                    'dashboard.view',
                ],
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']], // Recherche par nom
                $role // Données à créer/mettre à jour
            );
        }
    }
}
