<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\Project;
use App\Models\Attendance;
use App\Models\Expense;
use App\Models\ProgressUpdate;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    /**
     * Liste des rapports d'un projet
     */
    public function index(Request $request, $projectId)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if (!$companyId && !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez sélectionner une entreprise.',
            ], 400);
        }

        $project = Project::find($projectId);
        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Projet non trouvé.',
            ], 404);
        }

        if ($project->company_id !== $companyId && !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        $query = Report::where('project_id', $projectId)
            ->with('creator');

        // Filtre par type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Tri
        $sortBy = $request->get('sort_by', 'report_date');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $reports = $query->get();

        return response()->json([
            'success' => true,
            'data' => $reports,
        ], 200);
    }

    /**
     * Détails d'un rapport
     */
    public function show($id, $projectId)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if (!$companyId && !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez sélectionner une entreprise.',
            ], 400);
        }

        $project = Project::find($projectId);
        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Projet non trouvé.',
            ], 404);
        }

        if ($project->company_id !== $companyId && !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        $report = Report::with(['creator', 'project'])->find($id);

        if (!$report || $report->project_id != $projectId) {
            return response()->json([
                'success' => false,
                'message' => 'Rapport non trouvé.',
            ], 404);
        }

        return response()->json($report, 200);
    }

    /**
     * Générer un rapport
     */
    public function generate(Request $request, $projectId)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if (!$companyId && !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez sélectionner une entreprise.',
            ], 400);
        }

        $project = Project::find($projectId);
        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Projet non trouvé.',
            ], 404);
        }

        if ($project->company_id !== $companyId && !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'type' => 'required|in:journalier,hebdomadaire',
            'report_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:report_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $type = $request->input('type');
        $reportDate = Carbon::parse($request->input('report_date'));
        $endDate = $request->filled('end_date') 
            ? Carbon::parse($request->input('end_date'))
            : null;

        // Collecter les données
        $data = $type === 'journalier'
            ? $this->collectDailyData($project, $reportDate)
            : $this->collectWeeklyData($project, $reportDate, $endDate);

        // Générer le PDF
        try {
            $viewData = [
                'project' => $project,
                'data' => $data,
            ];
            
            // Mapper le type de rapport vers le nom de la vue
            $viewName = ($type === 'journalier') ? 'daily' : 'weekly';
            
            // Adapter les variables selon le type de rapport
            if ($type === 'journalier') {
                $viewData['date'] = $reportDate;
            } else {
                $viewData['startDate'] = $reportDate;
                $viewData['endDate'] = $endDate;
            }
            
            $pdf = Pdf::loadView('reports.pdf.' . $viewName, $viewData);

            $filename = 'rapport-' . $type . '-' . $project->name . '-' . $reportDate->format('Y-m-d') . '.pdf';
            $filePath = 'reports/' . $filename;
            
            // Sauvegarder le PDF
            Storage::disk('public')->put($filePath, $pdf->output());

            // Créer le rapport
            $report = Report::create([
                'project_id' => $projectId,
                'created_by' => $user->id,
                'type' => $type,
                'report_date' => $reportDate,
                'end_date' => $endDate,
                'data' => $data,
                'file_path' => $filePath,
            ]);

            return response()->json([
                'success' => true,
                'report' => $report->load('creator'),
                'file_url' => asset('storage/' . $filePath),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du PDF: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer un rapport
     */
    public function destroy($id, $projectId)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if (!$companyId && !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez sélectionner une entreprise.',
            ], 400);
        }

        $project = Project::find($projectId);
        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Projet non trouvé.',
            ], 404);
        }

        if ($project->company_id !== $companyId && !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        $report = Report::find($id);
        if (!$report || $report->project_id != $projectId) {
            return response()->json([
                'success' => false,
                'message' => 'Rapport non trouvé.',
            ], 404);
        }

        // Supprimer le fichier PDF
        if ($report->file_path) {
            Storage::disk('public')->delete($report->file_path);
        }

        $report->delete();

        return response()->json([
            'success' => true,
            'message' => 'Rapport supprimé avec succès.',
        ], 200);
    }

    /**
     * Collecter les données pour un rapport journalier
     */
    private function collectDailyData(Project $project, Carbon $date)
    {
        // Pointages du jour
        $attendances = Attendance::where('project_id', $project->id)
            ->whereDate('date', $date)
            ->with('employee')
            ->get();

        // Mises à jour du jour
        $progressUpdates = ProgressUpdate::where('project_id', $project->id)
            ->whereDate('created_at', $date)
            ->with('user')
            ->get();

        // Dépenses du jour
        $expenses = Expense::where('project_id', $project->id)
            ->whereDate('expense_date', $date)
            ->get();

        // Tâches
        $tasks = Task::where('project_id', $project->id)
            ->with('assignedEmployee')
            ->get();

        // Calculer les heures totales travaillées
        $totalHours = $attendances->sum(function($attendance) {
            return $attendance->hours_worked ?? 0;
        });

        return [
            'attendances' => $attendances,
            'progressUpdates' => $progressUpdates,
            'expenses' => $expenses,
            'tasks' => $tasks,
            'totalExpenses' => $expenses->sum('amount'),
            'totalEmployees' => $attendances->where('is_present', true)->count(),
            'totalHours' => $totalHours,
        ];
    }

    /**
     * Collecter les données pour un rapport hebdomadaire
     */
    private function collectWeeklyData(Project $project, Carbon $startDate, Carbon $endDate)
    {
        // Pointages de la semaine
        $attendances = Attendance::where('project_id', $project->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->with('employee')
            ->get();

        // Mises à jour de la semaine
        $progressUpdates = ProgressUpdate::where('project_id', $project->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('user')
            ->get();

        // Dépenses de la semaine
        $expenses = Expense::where('project_id', $project->id)
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->get();

        // Tâches
        $tasks = Task::where('project_id', $project->id)
            ->with('assignedEmployee')
            ->get();

        // Calculer les heures totales travaillées
        $totalHours = $attendances->sum(function($attendance) {
            return $attendance->hours_worked ?? 0;
        });

        // Calculer les heures supplémentaires totales
        $totalOvertime = $attendances->sum(function($attendance) {
            return $attendance->overtime_hours ?? 0;
        });

        // Tâches en retard
        $overdueTasks = $tasks->filter(function($task) use ($endDate) {
            return $task->deadline && 
                   \Carbon\Carbon::parse($task->deadline)->isBefore($endDate) && 
                   $task->status !== 'termine';
        })->count();

        // Évolution de l'avancement
        $progressEvolution = $progressUpdates->map(function($update) {
            return [
                'date' => $update->created_at->format('d/m/Y'),
                'progress' => $update->progress_percentage ?? 0,
            ];
        })->values()->toArray();

        return [
            'attendances' => $attendances,
            'progressUpdates' => $progressUpdates,
            'expenses' => $expenses,
            'tasks' => $tasks,
            'totalExpenses' => $expenses->sum('amount'),
            'totalEmployees' => $attendances->where('is_present', true)->count(),
            'totalHours' => $totalHours,
            'totalOvertime' => $totalOvertime,
            'expensesByType' => $expenses->groupBy('type')->map->sum('amount'),
            'tasksByStatus' => $tasks->groupBy('status')->map->count(),
            'overdueTasks' => $overdueTasks,
            'progressEvolution' => $progressEvolution,
        ];
    }
}

