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
        
        // Statistiques des projets
        $totalProjects = Project::forCompany($companyId)->count();
        $activeProjects = Project::forCompany($companyId)->where('status', 'en_cours')->count();
        $completedProjects = Project::forCompany($companyId)->where('status', 'termine')->count();
        $blockedProjects = Project::forCompany($companyId)->where('status', 'bloque')->count();
        
        // Budget total
        $totalBudget = Project::forCompany($companyId)->sum('budget');
        
        // Projets récents
        $recentProjects = Project::forCompany($companyId)
            ->with('creator')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        // Projets par statut
        $projectsByStatus = [
            'non_demarre' => Project::forCompany($companyId)->where('status', 'non_demarre')->count(),
            'en_cours' => $activeProjects,
            'termine' => $completedProjects,
            'bloque' => $blockedProjects,
        ];

        // Avancement moyen
        $averageProgress = Project::forCompany($companyId)
            ->where('status', 'en_cours')
            ->avg('progress') ?? 0;

        // Recherche globale
        $searchQuery = $request->get('search');
        $searchResults = [];
        
        if ($searchQuery) {
            // Rechercher dans les projets
            $searchResults['projects'] = Project::forCompany($companyId)
                ->where('name', 'like', '%' . $searchQuery . '%')
                ->orWhere('description', 'like', '%' . $searchQuery . '%')
                ->orWhere('client_name', 'like', '%' . $searchQuery . '%')
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

        if (!$companyId) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez sélectionner une entreprise pour accéder au dashboard.',
            ], 400);
        }

        // Statistiques des projets
        $totalProjects = Project::forCompany($companyId)->count();
        $activeProjects = Project::forCompany($companyId)->where('status', 'en_cours')->count();
        $completedProjects = Project::forCompany($companyId)->where('status', 'termine')->count();
        $blockedProjects = Project::forCompany($companyId)->where('status', 'bloque')->count();
        
        // Budget total
        $totalBudget = Project::forCompany($companyId)->sum('budget');
        
        // Avancement moyen
        $averageProgress = Project::forCompany($companyId)
            ->where('status', 'en_cours')
            ->avg('progress') ?? 0;

        return response()->json([
            'success' => true,
            'total_projects' => $totalProjects,
            'active_projects' => $activeProjects,
            'completed_projects' => $completedProjects,
            'blocked_projects' => $blockedProjects,
            'total_budget' => $totalBudget,
            'average_progress' => round($averageProgress, 2),
        ], 200);
    }
}
