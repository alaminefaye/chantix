<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CheckUserProjects extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:check-projects {email?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vérifier les projets assignés à un utilisateur';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        if ($email) {
            $user = User::where('email', $email)->first();
            if (!$user) {
                $this->error("Utilisateur non trouvé avec l'email: $email");
                return 1;
            }
            $users = collect([$user]);
        } else {
            $users = User::all();
        }

        foreach ($users as $user) {
            $this->info("\n=== Utilisateur: {$user->name} ({$user->email}) ===");
            $this->info("ID: {$user->id}");
            $this->info("Super Admin: " . ($user->isSuperAdmin() ? 'Oui' : 'Non'));
            
            if ($user->current_company_id) {
                $companyId = $user->current_company_id;
                $this->info("Entreprise actuelle ID: $companyId");
                
                $role = $user->roleInCompany($companyId);
                $roleName = $role ? $role->name : 'Aucun';
                $this->info("Rôle: $roleName");
                
                // Vérifier les projets assignés
                $assignedProjectIds = DB::table('project_user')
                    ->where('user_id', $user->id)
                    ->pluck('project_id')
                    ->toArray();
                
                $this->info("Projets assignés dans project_user: " . count($assignedProjectIds));
                if (!empty($assignedProjectIds)) {
                    $this->info("IDs des projets assignés: " . implode(', ', $assignedProjectIds));
                    
                    // Afficher les noms des projets
                    $projects = DB::table('projects')
                        ->whereIn('id', $assignedProjectIds)
                        ->select('id', 'name', 'company_id')
                        ->get();
                    
                    foreach ($projects as $project) {
                        $this->line("  - Projet #{$project->id}: {$project->name} (Company: {$project->company_id})");
                    }
                } else {
                    $this->warn("⚠️  Aucun projet assigné dans project_user!");
                }
                
                // Vérifier les projets créés
                $createdProjects = DB::table('projects')
                    ->where('created_by', $user->id)
                    ->where('company_id', $companyId)
                    ->count();
                
                $this->info("Projets créés: $createdProjects");
                
                // Vérifier ce que accessibleByUser retournerait
                $totalProjectsInCompany = DB::table('projects')
                    ->where('company_id', $companyId)
                    ->count();
                
                $this->info("Total projets dans l'entreprise: $totalProjectsInCompany");
                
                if (empty($assignedProjectIds) && in_array($roleName, ['superviseur', 'ingenieur'])) {
                    $this->warn("⚠️  Superviseur/Ingénieur sans projets assignés - verra TOUS les projets!");
                } elseif (!empty($assignedProjectIds)) {
                    $this->info("✅ A des projets assignés - devrait voir seulement ces projets");
                }
            } else {
                $this->warn("⚠️  Pas d'entreprise actuelle sélectionnée");
            }
        }

        return 0;
    }
}
