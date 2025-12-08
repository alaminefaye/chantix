<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Invitation;
use Illuminate\Support\Facades\DB;

class FixUserProjectAssignments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:fix-project-assignments {--dry-run : Afficher ce qui serait fait sans le faire}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Corriger les assignations de projets pour les utilisateurs invités';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('Mode DRY-RUN - Aucune modification ne sera effectuée');
        }

        // Trouver toutes les invitations acceptées
        $acceptedInvitations = Invitation::where('status', 'accepted')
            ->with('company')
            ->get();

        $this->info("Trouvé {$acceptedInvitations->count()} invitations acceptées");

        $fixed = 0;
        $skipped = 0;

        foreach ($acceptedInvitations as $invitation) {
            $user = User::where('email', $invitation->email)->first();
            
            if (!$user) {
                $this->warn("Utilisateur non trouvé pour l'invitation {$invitation->id} (email: {$invitation->email})");
                continue;
            }

            // Récupérer les projets de l'invitation
            $invitationProjects = $invitation->getProjectsDirectly();
            $invitationProjectIds = $invitationProjects->pluck('id')->toArray();

            // Vérifier les projets actuellement assignés
            $assignedProjectIds = DB::table('project_user')
                ->where('user_id', $user->id)
                ->pluck('project_id')
                ->toArray();

            $this->info("\n=== Invitation #{$invitation->id} ===");
            $this->info("Utilisateur: {$user->name} ({$user->email})");
            $this->info("Projets dans l'invitation: " . (empty($invitationProjectIds) ? 'AUCUN' : implode(', ', $invitationProjectIds)));
            $this->info("Projets actuellement assignés: " . (empty($assignedProjectIds) ? 'AUCUN' : implode(', ', $assignedProjectIds)));

            // Si l'invitation a des projets mais l'utilisateur n'en a pas, les assigner
            if (!empty($invitationProjectIds)) {
                $missingProjects = array_diff($invitationProjectIds, $assignedProjectIds);
                
                if (!empty($missingProjects)) {
                    $this->warn("⚠️  Projets manquants: " . implode(', ', $missingProjects));
                    
                    if (!$dryRun) {
                        foreach ($missingProjects as $projectId) {
                            // Vérifier que le projet existe et appartient à la bonne entreprise
                            $project = DB::table('projects')
                                ->where('id', $projectId)
                                ->where('company_id', $invitation->company_id)
                                ->first();
                            
                            if ($project) {
                                // Vérifier qu'il n'est pas déjà assigné (double vérification)
                                $exists = DB::table('project_user')
                                    ->where('user_id', $user->id)
                                    ->where('project_id', $projectId)
                                    ->exists();
                                
                                if (!$exists) {
                                    DB::table('project_user')->insert([
                                        'user_id' => $user->id,
                                        'project_id' => $projectId,
                                        'created_at' => now(),
                                        'updated_at' => now(),
                                    ]);
                                    $this->info("✅ Projet #{$projectId} assigné à l'utilisateur");
                                    $fixed++;
                                }
                            } else {
                                $this->error("❌ Projet #{$projectId} non trouvé ou n'appartient pas à l'entreprise");
                            }
                        }
                    } else {
                        $this->info("DRY-RUN: Assignerait les projets: " . implode(', ', $missingProjects));
                        $fixed++;
                    }
                } else {
                    $this->info("✅ Tous les projets sont déjà assignés");
                    $skipped++;
                }
            } else {
                $this->info("ℹ️  Aucun projet dans l'invitation");
                $skipped++;
            }
        }

        $this->info("\n=== Résumé ===");
        $this->info("Corrigé: $fixed");
        $this->info("Ignoré: $skipped");

        return 0;
    }
}
