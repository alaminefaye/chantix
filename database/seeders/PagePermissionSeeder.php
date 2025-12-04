<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PagePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pages = [
            // Dashboard
            'dashboard.view',
            
            // Companies
            'companies.view',
            'companies.create',
            'companies.update',
            'companies.delete',
            'companies.switch',
            
            // Users
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'users.assign_role',
            
            // Projects
            'projects.view',
            'projects.create',
            'projects.update',
            'projects.delete',
            'projects.manage_team',
            'projects.timeline',
            'projects.gallery',
            
            // Materials
            'materials.view',
            'materials.create',
            'materials.update',
            'materials.delete',
            'materials.import',
            'materials.transfer',
            
            // Employees
            'employees.view',
            'employees.create',
            'employees.update',
            'employees.delete',
            'employees.import',
            'employees.assign',
            
            // Attendances
            'attendances.view',
            'attendances.create',
            'attendances.update',
            'attendances.delete',
            'attendances.checkin',
            'attendances.checkout',
            
            // Expenses
            'expenses.view',
            'expenses.create',
            'expenses.update',
            'expenses.delete',
            
            // Tasks
            'tasks.view',
            'tasks.create',
            'tasks.update',
            'tasks.delete',
            
            // Reports
            'reports.view',
            'reports.generate',
            'reports.daily',
            'reports.weekly',
            'reports.export',
            
            // Progress
            'progress.view',
            'progress.create',
            'progress.update',
            'progress.delete',
            'progress.validate',
            
            // Comments
            'comments.view',
            'comments.create',
            'comments.delete',
            
            // Profile
            'profile.view',
            'profile.update',
            
            // Notifications
            'notifications.view',
            
            // Admin
            'admin.users_validation',
        ];

        foreach ($pages as $page) {
            Permission::firstOrCreate(['name' => $page]);
        }
    }
}

