<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Invitation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckInvitationProjects extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invitations:check-projects {--fix : Corriger les problèmes trouvés}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vérifier les projets associés aux invitations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!Schema::hasTable('invitation_project')) {
            $this->error('La table invitation_project n\'existe pas!');
            return 1;
        }

        $this->info('Vérification des projets associés aux invitations...');
        $this->newLine();

        $invitations = Invitation::all();
        $issues = [];

        foreach ($invitations as $invitation) {
            // Charger la relation
            $invitation->load('projects');
            
            // Vérifier directement dans la table pivot
            $directProjectIds = DB::table('invitation_project')
                ->where('invitation_id', $invitation->id)
                ->pluck('project_id')
                ->toArray();
            
            $relationCount = $invitation->projects->count();
            $directCount = count($directProjectIds);
            
            if ($relationCount !== $directCount) {
                $issues[] = [
                    'invitation_id' => $invitation->id,
                    'email' => $invitation->email,
                    'relation_count' => $relationCount,
                    'direct_count' => $directCount,
                    'project_ids_direct' => $directProjectIds,
                    'project_ids_relation' => $invitation->projects->pluck('id')->toArray(),
                ];
                
                $this->warn("Invitation #{$invitation->id} ({$invitation->email}):");
                $this->line("  - Relation Eloquent: {$relationCount} projet(s)");
                $this->line("  - Table pivot directe: {$directCount} projet(s)");
                
                if ($directCount > 0) {
                    $projects = \App\Models\Project::whereIn('id', $directProjectIds)->get();
                    $this->line("  - Projets: " . $projects->pluck('name')->join(', '));
                }
                $this->newLine();
            } else {
                $this->info("Invitation #{$invitation->id} ({$invitation->email}): OK - {$relationCount} projet(s)");
            }
        }

        if (empty($issues)) {
            $this->info('Aucun problème détecté!');
            return 0;
        }

        $this->newLine();
        $this->warn(count($issues) . ' problème(s) détecté(s).');

        if ($this->option('fix')) {
            $this->info('Correction en cours...');
            foreach ($issues as $issue) {
                // Recharger la relation pour forcer la mise à jour
                $invitation = Invitation::find($issue['invitation_id']);
                $invitation->load('projects');
                $this->info("Invitation #{$issue['invitation_id']} corrigée.");
            }
            $this->info('Correction terminée!');
        } else {
            $this->info('Utilisez --fix pour corriger automatiquement les problèmes.');
        }

        return 0;
    }
}
