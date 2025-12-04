<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

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

        $this->command->info('Super administrateur créé/mis à jour avec succès !');
        $this->command->info('Email: admin@admin.com');
        $this->command->info('Mot de passe: passer123');
    }
}
