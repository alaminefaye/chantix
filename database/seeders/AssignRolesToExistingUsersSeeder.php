<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Support\Facades\DB;

class AssignRolesToExistingUsersSeeder extends Seeder
{
    /**
     * Assigner les rôles Spatie aux utilisateurs existants basés sur leur rôle dans company_user
     */
    public function run(): void
    {
        // Pour chaque utilisateur, récupérer ses rôles depuis company_user et les assigner dans Spatie
        $users = User::all();
        
        foreach ($users as $user) {
            // Récupérer les rôles de l'utilisateur depuis company_user
            $companyRoles = DB::table('company_user')
                ->where('user_id', $user->id)
                ->whereNotNull('role_id')
                ->pluck('role_id')
                ->unique();
            
            $spatieRoles = [];
            
            foreach ($companyRoles as $roleId) {
                // Trouver le rôle dans la table roles
                $role = Role::find($roleId);
                
                if ($role) {
                    // Trouver le rôle Spatie correspondant
                    $spatieRole = SpatieRole::where('name', $role->name)
                        ->where('guard_name', 'web')
                        ->first();
                    
                    if ($spatieRole) {
                        $spatieRoles[] = $spatieRole;
                    }
                }
            }
            
            // Si l'utilisateur est super admin, assigner le rôle super_admin
            if ($user->is_super_admin) {
                $superAdminRole = SpatieRole::where('name', 'super_admin')
                    ->where('guard_name', 'web')
                    ->first();
                if ($superAdminRole && !in_array($superAdminRole, $spatieRoles)) {
                    $spatieRoles[] = $superAdminRole;
                }
            }
            
            // Si l'utilisateur est super admin, assigner le rôle super_admin
            if ($user->is_super_admin) {
                $superAdminRole = SpatieRole::where('name', 'super_admin')
                    ->where('guard_name', 'web')
                    ->first();
                if ($superAdminRole) {
                    $spatieRoles = [$superAdminRole]; // Super admin a seulement ce rôle
                }
            }
            
            // Assigner les rôles Spatie à l'utilisateur
            if (!empty($spatieRoles)) {
                // Utiliser DB directement pour éviter le problème de relation null
                $roleIds = array_map(fn($r) => $r->id, $spatieRoles);
                
                // Supprimer les anciens rôles
                DB::table('model_has_roles')
                    ->where('model_type', User::class)
                    ->where('model_id', $user->id)
                    ->where('guard_name', 'web')
                    ->delete();
                
                // Insérer les nouveaux rôles
                foreach ($roleIds as $roleId) {
                    DB::table('model_has_roles')->insertOrIgnore([
                        'role_id' => $roleId,
                        'model_type' => User::class,
                        'model_id' => $user->id,
                        'guard_name' => 'web',
                    ]);
                }
                
                $this->command->info("Rôles assignés à {$user->email}: " . implode(', ', array_map(fn($r) => $r->name, $spatieRoles)));
            } else {
                $this->command->warn("Aucun rôle trouvé pour {$user->email}");
            }
        }
        
        $this->command->info('Assignation des rôles terminée !');
    }
}

