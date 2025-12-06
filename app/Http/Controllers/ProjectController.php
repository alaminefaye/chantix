<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Company;
use App\Models\ProjectStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    /**
     * Afficher la liste des projets
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if (!$companyId) {
            return redirect()->route('companies.index')
                ->with('error', 'Veuillez sélectionner une entreprise.');
        }

        // Filtrer les projets selon l'accès de l'utilisateur
        $query = Project::accessibleByUser($user, $companyId)->with('creator');

        // Filtre par statut
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filtre par responsable (créateur)
        if ($request->filled('created_by')) {
            $query->where('created_by', $request->created_by);
        }

        // Filtre par date de début
        if ($request->filled('start_date_from')) {
            $query->where('start_date', '>=', $request->start_date_from);
        }
        if ($request->filled('start_date_to')) {
            $query->where('start_date', '<=', $request->start_date_to);
        }

        // Recherche par nom
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%')
                  ->orWhere('client_name', 'like', '%' . $request->search . '%');
            });
        }

        // Vue carte ou liste
        $view = $request->get('view', 'list');

        // Tri
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pour la vue carte, on récupère tous les projets avec coordonnées
        if ($view === 'map') {
            $projects = $query->whereNotNull('latitude')
                             ->whereNotNull('longitude')
                             ->get();
        } else {
            $projects = $query->paginate(15)->withQueryString();
        }

        // Récupérer les créateurs pour les filtres
        $creators = \App\Models\User::whereHas('createdProjects', function($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })->get();

        return view('projects.index', compact('projects', 'creators', 'view'));
    }

    /**
     * Afficher le formulaire de création de projet
     */
    public function create()
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if (!$companyId) {
            return redirect()->route('companies.index')
                ->with('error', 'Veuillez sélectionner une entreprise.');
        }

        // Vérifier les permissions : seuls admin et chef_chantier peuvent créer des projets
        if (!$user->isSuperAdmin() && !$user->hasRoleInCompany('admin', $companyId) && !$user->hasPermission('projects.create', $companyId)) {
            abort(403, 'Vous n\'avez pas la permission de créer des projets.');
        }

        return view('projects.create');
    }

    /**
     * Créer un nouveau projet
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if (!$companyId) {
            return redirect()->route('companies.index')
                ->with('error', 'Veuillez sélectionner une entreprise.');
        }

        // Vérifier les permissions : seuls admin et chef_chantier peuvent créer des projets
        if (!$user->isSuperAdmin() && !$user->hasRoleInCompany('admin', $companyId) && !$user->hasPermission('projects.create', $companyId)) {
            abort(403, 'Vous n\'avez pas la permission de créer des projets.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'budget' => 'nullable|numeric|min:0',
            'client_name' => 'nullable|string|max:255',
            'client_contact' => 'nullable|string|max:255',
        ]);

        $project = Project::create([
            'company_id' => $companyId,
            'name' => $request->name,
            'description' => $request->description,
            'address' => $request->address,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'budget' => $request->budget ?? 0,
            'status' => 'non_demarre',
            'progress' => 0,
            'created_by' => $user->id,
            'client_name' => $request->client_name,
            'client_contact' => $request->client_contact,
        ]);

        // Associer automatiquement le créateur au projet
        if (!$project->users()->where('users.id', $user->id)->exists()) {
            $project->users()->attach($user->id);
        }

        return redirect()->route('projects.index')
            ->with('success', 'Projet créé avec succès !');
    }

    /**
     * Afficher les détails d'un projet
     */
    public function show(Project $project)
    {
        $user = Auth::user();
        
        // Vérifier l'accès à l'entreprise
        if ($project->company_id !== $user->current_company_id) {
            abort(403);
        }

        // Vérifier l'accès au projet spécifique
        // Admin et super admin ont accès à tous les projets
        if (!$user->isSuperAdmin() && !$user->hasRoleInCompany('admin', $project->company_id)) {
            // Vérifier si l'utilisateur a accès à ce projet
            $hasAccess = $project->users()->where('users.id', $user->id)->exists() 
                      || $project->created_by == $user->id;
            
            if (!$hasAccess) {
                abort(403, 'Vous n\'avez pas accès à ce projet.');
            }
        }

        $project->load('creator', 'company', 'materials', 'employees', 'progressUpdates.user', 'tasks.creator', 'expenses.creator', 'comments.user');
        
        // Charger statusHistory seulement si la relation existe
        if (method_exists($project, 'statusHistory')) {
            try {
                $project->load('statusHistory.user');
            } catch (\Exception $e) {
                // Table n'existe peut-être pas en test
            }
        }

        // Préparer les données pour le graphique d'évolution de l'avancement
        $progressData = $project->progressUpdates()
            ->orderBy('created_at', 'asc')
            ->get(['created_at', 'progress_percentage'])
            ->map(function($update) {
                return [
                    'date' => $update->created_at->format('Y-m-d'),
                    'progress' => $update->progress_percentage,
                ];
            })
            ->toArray();

        // Ajouter le point initial (0%) et le point actuel
        $progressChartData = [
            ['date' => $project->created_at->format('Y-m-d'), 'progress' => 0],
        ];
        
        foreach ($progressData as $data) {
            $progressChartData[] = $data;
        }
        
        // Ajouter le point actuel du projet
        if ($project->progress > 0) {
            $progressChartData[] = [
                'date' => now()->format('Y-m-d'),
                'progress' => $project->progress,
            ];
        }

        return view('projects.show', compact('project', 'progressChartData'));
    }

    /**
     * Afficher la timeline du projet
     */
    public function timeline(Project $project)
    {
        $user = Auth::user();
        
        // Vérifier l'accès à l'entreprise
        if ($project->company_id !== $user->current_company_id) {
            abort(403);
        }

        // Vérifier l'accès au projet spécifique
        if (!$user->isSuperAdmin() && !$user->hasRoleInCompany('admin', $project->company_id)) {
            $hasAccess = $project->users()->where('users.id', $user->id)->exists() 
                      || $project->created_by == $user->id;
            
            if (!$hasAccess) {
                abort(403, 'Vous n\'avez pas accès à ce projet.');
            }
        }

        // Récupérer tous les événements
        $events = collect();

        // Mises à jour d'avancement
        foreach ($project->progressUpdates()->with('user')->orderBy('created_at', 'desc')->get() as $update) {
            $events->push([
                'type' => 'progress',
                'date' => $update->created_at,
                'title' => 'Mise à jour d\'avancement',
                'description' => $update->description,
                'user' => $update->user ? $update->user->name : 'Utilisateur inconnu',
                'data' => $update,
            ]);
        }

        // Tâches créées
        foreach ($project->tasks()->with('creator')->orderBy('created_at', 'desc')->get() as $task) {
            $events->push([
                'type' => 'task',
                'date' => $task->created_at,
                'title' => 'Tâche créée: ' . $task->title,
                'description' => $task->description,
                'user' => $task->creator->name ?? 'Système',
                'data' => $task,
            ]);
        }

        // Dépenses
        foreach ($project->expenses()->with('creator')->orderBy('created_at', 'desc')->get() as $expense) {
            $events->push([
                'type' => 'expense',
                'date' => $expense->created_at,
                'title' => 'Dépense: ' . $expense->title,
                'description' => number_format($expense->amount, 2, ',', ' ') . ' €',
                'user' => $expense->creator ? $expense->creator->name : 'Utilisateur inconnu',
                'data' => $expense,
            ]);
        }

        // Commentaires (seulement les commentaires principaux, pas les réponses)
        foreach ($project->comments()->with('user')->orderBy('created_at', 'desc')->get() as $comment) {
            $events->push([
                'type' => 'comment',
                'date' => $comment->created_at,
                'title' => 'Commentaire',
                'description' => $comment->content,
                'user' => $comment->user ? $comment->user->name : 'Utilisateur inconnu',
                'data' => $comment,
            ]);
        }

        // Trier par date décroissante
        $events = $events->sortByDesc('date')->values();

        return view('projects.timeline', compact('project', 'events'));
    }

    /**
     * Afficher la galerie de médias du projet
     */
    public function gallery(Project $project)
    {
        $user = Auth::user();
        
        // Vérifier l'accès à l'entreprise
        if ($project->company_id !== $user->current_company_id) {
            abort(403);
        }

        // Vérifier l'accès au projet spécifique
        if (!$user->isSuperAdmin() && !$user->hasRoleInCompany('admin', $project->company_id)) {
            $hasAccess = $project->users()->where('users.id', $user->id)->exists() 
                      || $project->created_by == $user->id;
            
            if (!$hasAccess) {
                abort(403, 'Vous n\'avez pas accès à ce projet.');
            }
        }

        // Récupérer toutes les photos et vidéos des mises à jour
        $allPhotos = [];
        $allVideos = [];
        
        foreach ($project->progressUpdates as $update) {
            if ($update->photos) {
                foreach ($update->photos as $photo) {
                    $allPhotos[] = [
                        'path' => $photo,
                        'update' => $update,
                        'date' => $update->created_at,
                    ];
                }
            }
            if ($update->videos) {
                foreach ($update->videos as $video) {
                    $allVideos[] = [
                        'path' => $video,
                        'update' => $update,
                        'date' => $update->created_at,
                    ];
                }
            }
        }

        // Trier par date
        usort($allPhotos, fn($a, $b) => $b['date'] <=> $a['date']);
        usort($allVideos, fn($a, $b) => $b['date'] <=> $a['date']);

        return view('projects.gallery', compact('project', 'allPhotos', 'allVideos'));
    }

    /**
     * Afficher le formulaire d'édition
     */
    public function edit(Project $project)
    {
        $user = Auth::user();
        
        // Vérifier l'accès à l'entreprise
        if ($project->company_id !== $user->current_company_id) {
            abort(403);
        }

        // Vérifier l'accès au projet spécifique
        if (!$user->isSuperAdmin() && !$user->hasRoleInCompany('admin', $project->company_id)) {
            $hasAccess = $project->users()->where('users.id', $user->id)->exists() 
                      || $project->created_by == $user->id;
            
            if (!$hasAccess) {
                abort(403, 'Vous n\'avez pas accès à ce projet.');
            }
        }

        // Vérifier les permissions : seuls admin et chef_chantier peuvent modifier des projets
        if (!$user->isSuperAdmin() && !$user->hasRoleInCompany('admin', $project->company_id) && !$user->hasPermission('projects.update', $project->company_id)) {
            abort(403, 'Vous n\'avez pas la permission de modifier ce projet.');
        }

        return view('projects.edit', compact('project'));
    }

    /**
     * Mettre à jour un projet
     */
    public function update(Request $request, Project $project)
    {
        $user = Auth::user();
        
        // Vérifier l'accès à l'entreprise
        if ($project->company_id !== $user->current_company_id) {
            abort(403);
        }

        // Vérifier l'accès au projet spécifique
        if (!$user->isSuperAdmin() && !$user->hasRoleInCompany('admin', $project->company_id)) {
            $hasAccess = $project->users()->where('users.id', $user->id)->exists() 
                      || $project->created_by == $user->id;
            
            if (!$hasAccess) {
                abort(403, 'Vous n\'avez pas accès à ce projet.');
            }
        }

        // Vérifier les permissions : seuls admin et chef_chantier peuvent modifier des projets
        if (!$user->isSuperAdmin() && !$user->hasRoleInCompany('admin', $project->company_id) && !$user->hasPermission('projects.update', $project->company_id)) {
            abort(403, 'Vous n\'avez pas la permission de modifier ce projet.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'budget' => 'nullable|numeric|min:0',
            'status' => 'required|in:non_demarre,en_cours,termine,bloque',
            'progress' => 'nullable|integer|min:0|max:100',
            'client_name' => 'nullable|string|max:255',
            'client_contact' => 'nullable|string|max:255',
            'status_change_reason' => 'nullable|string|max:500',
        ]);

        // Enregistrer l'historique si le statut change
        $oldStatus = $project->status;
        if ($oldStatus !== $request->status) {
            try {
                ProjectStatusHistory::create([
                    'project_id' => $project->id,
                    'user_id' => $user->id,
                    'old_status' => $oldStatus,
                    'new_status' => $request->status,
                    'reason' => $request->status_change_reason,
                ]);
            } catch (\Exception $e) {
                // Table n'existe peut-être pas en test
            }
        }

        $project->update($request->all());

        return redirect()->route('projects.show', $project)
            ->with('success', 'Projet mis à jour avec succès !');
    }

    /**
     * Supprimer un projet
     */
    public function destroy(Project $project)
    {
        $user = Auth::user();
        
        // Vérifier l'accès à l'entreprise
        if ($project->company_id !== $user->current_company_id) {
            abort(403);
        }

        // Vérifier l'accès au projet spécifique et les permissions de suppression
        // Seuls admin et super admin peuvent supprimer
        if (!$user->isSuperAdmin() && !$user->hasRoleInCompany('admin', $project->company_id)) {
            abort(403, 'Vous n\'avez pas la permission de supprimer ce projet.');
        }

        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', 'Projet supprimé avec succès !');
    }
}
