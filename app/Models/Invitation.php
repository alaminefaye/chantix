<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Invitation extends Model
{
    protected $fillable = [
        'company_id',
        'project_id',
        'invited_by',
        'role_id',
        'email',
        'token',
        'status',
        'expires_at',
        'accepted_at',
        'message',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    /**
     * L'entreprise
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * L'utilisateur qui a envoyé l'invitation
     */
    public function inviter()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Le rôle assigné
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Le projet concerné (optionnel) - Relation legacy pour compatibilité
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Les projets concernés (relation many-to-many)
     */
    public function projects()
    {
        return $this->belongsToMany(Project::class, 'invitation_project')
                    ->withTimestamps();
    }

    /**
     * Récupérer les projets directement depuis la DB (sans cache)
     * Utiliser cette méthode au lieu de $invitation->projects pour éviter les problèmes de cache
     * Cette méthode utilise des requêtes DB brutes pour éviter TOUT cache Eloquent
     */
    public function getProjectsDirectly()
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('invitation_project')) {
            // Fallback: utiliser l'ancienne colonne project_id
            if ($this->project_id) {
                $projectData = \Illuminate\Support\Facades\DB::table('projects')
                    ->where('id', $this->project_id)
                    ->first();
                if ($projectData) {
                    $project = new Project();
                    $project->fill((array) $projectData);
                    $project->exists = true;
                    return collect([$project]);
                }
            }
            return collect([]);
        }

        try {
            // Requête directe sur la table pivot (ne dépend pas du cache)
            $projectIds = \Illuminate\Support\Facades\DB::table('invitation_project')
                ->where('invitation_id', $this->id)
                ->pluck('project_id')
                ->toArray();

            if (empty($projectIds)) {
                return collect([]);
            }

            // Récupérer les projets avec une requête DB brute (pas Eloquent pour éviter le cache)
            $projectsData = \Illuminate\Support\Facades\DB::table('projects')
                ->whereIn('id', $projectIds)
                ->get();

            // Convertir les résultats DB en modèles Project sans utiliser le cache
            $projects = collect();
            foreach ($projectsData as $projectData) {
                $project = new Project();
                // Convertir l'objet stdClass en tableau
                $dataArray = is_object($projectData) ? (array) $projectData : $projectData;
                $project->fill($dataArray);
                $project->exists = true;
                // S'assurer que l'ID est bien défini
                if (isset($dataArray['id'])) {
                    $project->id = (int) $dataArray['id'];
                }
                $projects->push($project);
            }

            return $projects;
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération directe des projets pour l\'invitation ' . $this->id . ': ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return collect([]);
        }
    }

    /**
     * Générer un token unique
     */
    public static function generateToken()
    {
        do {
            $token = Str::random(64);
        } while (self::where('token', $token)->exists());

        return $token;
    }

    /**
     * Vérifier si l'invitation est expirée
     */
    public function isExpired()
    {
        return $this->expires_at->isPast();
    }

    /**
     * Vérifier si l'invitation est valide
     */
    public function isValid()
    {
        return $this->status === 'pending' && !$this->isExpired();
    }

    /**
     * Marquer comme acceptée
     */
    public function markAsAccepted()
    {
        $this->status = 'accepted';
        $this->accepted_at = now();
        $this->save();
    }

    /**
     * Marquer comme annulée
     */
    public function markAsCancelled()
    {
        $this->status = 'cancelled';
        $this->save();
    }

    /**
     * Scope pour les invitations en attente
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending')
            ->where('expires_at', '>', now());
    }

    /**
     * Scope pour une entreprise
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
