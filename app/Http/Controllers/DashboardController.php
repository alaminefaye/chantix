<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Project;
use App\Models\Company;
use App\Models\Material;
use App\Models\Employee;
use App\Models\Task;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if (!$companyId) {
            return redirect()->route('companies.index')
                ->with('info', 'Veuillez sélectionner une entreprise pour accéder au dashboard.');
        }

        $company = Company::findOrFail($companyId);
        
        // Utiliser accessibleByUser pour filtrer selon l'accès
        $baseQuery = Project::accessibleByUser($user, $companyId);
        
        // Statistiques des projets
        $totalProjects = (clone $baseQuery)->count();
        $activeProjects = (clone $baseQuery)->where('status', 'en_cours')->count();
        $completedProjects = (clone $baseQuery)->where('status', 'termine')->count();
        $blockedProjects = (clone $baseQuery)->where('status', 'bloque')->count();
        
        // Budget total
        $totalBudget = (clone $baseQuery)->sum('budget');
        
        // Projets récents
        $recentProjects = (clone $baseQuery)
            ->with('creator')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        // Projets par statut
        $projectsByStatus = [
            'non_demarre' => (clone $baseQuery)->where('status', 'non_demarre')->count(),
            'en_cours' => $activeProjects,
            'termine' => $completedProjects,
            'bloque' => $blockedProjects,
        ];

        // Avancement moyen
        $averageProgress = (clone $baseQuery)
            ->where('status', 'en_cours')
            ->avg('progress') ?? 0;

        // Recherche globale
        $searchQuery = $request->get('search');
        $searchResults = [];
        
        if ($searchQuery) {
            // Rechercher dans les projets (filtrés par accès)
            $searchResults['projects'] = Project::accessibleByUser($user, $companyId)
                ->where(function($q) use ($searchQuery) {
                    $q->where('name', 'like', '%' . $searchQuery . '%')
                      ->orWhere('description', 'like', '%' . $searchQuery . '%')
                      ->orWhere('client_name', 'like', '%' . $searchQuery . '%');
                })
                ->limit(5)
                ->get();
            
            // Rechercher dans les matériaux
            $searchResults['materials'] = Material::forCompany($companyId)
                ->where(function($q) use ($searchQuery) {
                    $q->where('name', 'like', '%' . $searchQuery . '%')
                      ->orWhere('description', 'like', '%' . $searchQuery . '%');
                })
                ->limit(5)
                ->get();
            
            // Rechercher dans les employés
            $searchResults['employees'] = Employee::forCompany($companyId)
                ->where(function($q) use ($searchQuery) {
                    $q->where('first_name', 'like', '%' . $searchQuery . '%')
                      ->orWhere('last_name', 'like', '%' . $searchQuery . '%')
                      ->orWhere('email', 'like', '%' . $searchQuery . '%');
                })
                ->limit(5)
                ->get();
            
            // Rechercher dans les tâches
            $searchResults['tasks'] = Task::whereHas('project', function($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                })
                ->where('title', 'like', '%' . $searchQuery . '%')
                ->orWhere('description', 'like', '%' . $searchQuery . '%')
                ->limit(5)
                ->get();
        }

        return view('dashboard.index', compact(
            'company',
            'totalProjects',
            'activeProjects',
            'completedProjects',
            'blockedProjects',
            'totalBudget',
            'recentProjects',
            'projectsByStatus',
            'averageProgress',
            'searchQuery',
            'searchResults'
        ));
    }

    /**
     * API endpoint pour le dashboard
     */
    public function apiIndex(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        // Si super admin et pas d'entreprise, on retourne des statistiques globales
        if ($user->isSuperAdmin() && !$companyId) {
            $totalProjects = Project::count();
            $activeProjects = Project::where('status', 'en_cours')->count();
            $completedProjects = Project::where('status', 'termine')->count();
            $blockedProjects = Project::where('status', 'bloque')->count();
            $totalBudget = Project::sum('budget');
            $averageProgress = Project::where('status', 'en_cours')->avg('progress') ?? 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'total_projects' => $totalProjects,
                    'active_projects' => $activeProjects,
                    'completed_projects' => $completedProjects,
                    'blocked_projects' => $blockedProjects,
                    'total_budget' => $totalBudget,
                    'average_progress' => round($averageProgress, 2),
                ],
            ], 200);
        }

        // Pour les autres utilisateurs, une entreprise est requise
        if (!$companyId) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez sélectionner une entreprise pour accéder au dashboard.',
            ], 400);
        }

        // Utiliser accessibleByUser pour filtrer selon l'accès
        $baseQuery = Project::accessibleByUser($user, $companyId);
        
        // Statistiques des projets
        $totalProjects = (clone $baseQuery)->count();
        $activeProjects = (clone $baseQuery)->where('status', 'en_cours')->count();
        $completedProjects = (clone $baseQuery)->where('status', 'termine')->count();
        $blockedProjects = (clone $baseQuery)->where('status', 'bloque')->count();
        
        // Budget total
        $totalBudget = (clone $baseQuery)->sum('budget');
        
        // Avancement moyen
        $averageProgress = (clone $baseQuery)
            ->where('status', 'en_cours')
            ->avg('progress') ?? 0;

        return response()->json([
            'success' => true,
            'data' => [
                'total_projects' => $totalProjects,
                'active_projects' => $activeProjects,
                'completed_projects' => $completedProjects,
                'blocked_projects' => $blockedProjects,
                'total_budget' => $totalBudget,
                'average_progress' => round($averageProgress, 2),
            ],
        ], 200);
    }
}
