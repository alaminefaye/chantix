<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer le super admin
        $superAdmin = User::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Super Administrateur',
                'password' => Hash::make('passer123'),
                'is_super_admin' => true,
                'is_verified' => true,
                'email_verified_at' => now(),
            ]
        );

        // Assigner le rôle super_admin de Spatie Permissions
        $superAdminRole = Role::where('name', 'super_admin')->where('guard_name', 'web')->first();
        if ($superAdminRole) {
            $superAdmin->syncRoles([$superAdminRole]);
        }

        $this->command->info('Super administrateur créé/mis à jour avec succès !');
        $this->command->info('Email: admin@admin.com');
        $this->command->info('Mot de passe: passer123');
    }
}
