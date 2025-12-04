<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Company;
use App\Models\Role;

class FixUserPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:fix-permissions {email} {--company=} {--make-admin}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnostiquer et corriger les permissions d\'un utilisateur';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("Utilisateur avec l'email {$email} introuvable.");
            return 1;
        }

        $this->info("=== Diagnostic pour {$user->name} ({$user->email}) ===");
        $this->info("Super Admin: " . ($user->isSuperAdmin() ? 'OUI' : 'NON'));
        $this->info("Entreprise actuelle: " . ($user->current_company_id ?? 'Aucune'));
        
        $this->info("\n=== Entreprises associées ===");
        $companies = $user->companies()->get();
        
        if ($companies->isEmpty()) {
            $this->warn("L'utilisateur n'est associé à aucune entreprise!");
        } else {
            foreach ($companies as $company) {
                $role = $user->roleInCompany($company->id);
                $this->info("  - {$company->name} (ID: {$company->id})");
                $this->info("    Rôle: " . ($role ? $role->name : 'AUCUN'));
                $this->info("    Admin: " . ($user->hasRoleInCompany('admin', $company->id) ? 'OUI' : 'NON'));
            }
        }

        $companyId = $this->option('company');
        if ($companyId) {
            $company = Company::find($companyId);
            if (!$company) {
                $this->error("Entreprise avec l'ID {$companyId} introuvable.");
                return 1;
            }

            $this->info("\n=== Vérification pour l'entreprise: {$company->name} ===");
            $canAccess = $user->canAccessCompanyResource($company->id);
            $isAdmin = $user->hasRoleInCompany('admin', $company->id);
            $role = $user->roleInCompany($company->id);

            $this->info("Peut accéder: " . ($canAccess ? 'OUI' : 'NON'));
            $this->info("Est admin: " . ($isAdmin ? 'OUI' : 'NON'));
            $this->info("Rôle actuel: " . ($role ? $role->name : 'AUCUN'));

            if ($this->option('make-admin')) {
                if (!$canAccess) {
                    // Ajouter l'utilisateur à l'entreprise
                    $adminRole = Role::where('name', 'admin')->first();
                    if (!$adminRole) {
                        $this->error("Le rôle 'admin' n'existe pas. Exécutez d'abord: php artisan db:seed --class=RoleSeeder");
                        return 1;
                    }

                    $user->companies()->attach($company->id, [
                        'role_id' => $adminRole->id,
                        'is_active' => true,
                        'joined_at' => now(),
                    ]);

                    $this->info("✓ Utilisateur ajouté à l'entreprise avec le rôle admin.");
                } elseif (!$isAdmin) {
                    // Mettre à jour le rôle
                    $adminRole = Role::where('name', 'admin')->first();
                    if (!$adminRole) {
                        $this->error("Le rôle 'admin' n'existe pas. Exécutez d'abord: php artisan db:seed --class=RoleSeeder");
                        return 1;
                    }

                    $user->companies()->updateExistingPivot($company->id, [
                        'role_id' => $adminRole->id,
                    ]);

                    $this->info("✓ Rôle mis à jour vers 'admin'.");
                } else {
                    $this->info("L'utilisateur est déjà admin de cette entreprise.");
                }

                // Définir l'entreprise comme actuelle si nécessaire
                if (!$user->current_company_id) {
                    $user->current_company_id = $company->id;
                    $user->save();
                    $this->info("✓ Entreprise définie comme actuelle.");
                }
            }
        }

        return 0;
    }
}

