<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Project;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Project $project)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if ($project->company_id !== $companyId) {
            abort(403, 'Accès non autorisé.');
        }

        $status = request()->get('status');
        $query = $project->tasks()->with('creator', 'assignedEmployee');

        if ($status) {
            $query->withStatus($status);
        }

        $tasks = $query->orderBy('deadline', 'asc')->orderBy('priority', 'desc')->get();

        // Statistiques
        $totalTasks = $project->tasks()->count();
        $completedTasks = $project->tasks()->withStatus('termine')->count();
        $overdueTasks = $project->tasks()->overdue()->count();
        $dueSoonTasks = $project->tasks()->dueSoon()->count();

        $view = request()->get('view', 'list'); // list, calendar, kanban
        
        if ($view === 'calendar') {
            return $this->calendar($project, $tasks, $totalTasks, $completedTasks, $overdueTasks, $dueSoonTasks);
        } elseif ($view === 'kanban') {
            return $this->kanban($project, $tasks, $totalTasks, $completedTasks, $overdueTasks, $dueSoonTasks);
        }

        return view('tasks.index', compact('project', 'tasks', 'totalTasks', 'completedTasks', 'overdueTasks', 'dueSoonTasks', 'status'));
    }

    /**
     * Vue calendrier des tâches
     */
    private function calendar(Project $project, $tasks, $totalTasks, $completedTasks, $overdueTasks, $dueSoonTasks)
    {
        // Grouper les tâches par date
        $tasksByDate = $tasks->groupBy(function($task) {
            return $task->due_date ? $task->due_date->format('Y-m-d') : 'no-date';
        });

        return view('tasks.calendar', compact('project', 'tasks', 'tasksByDate', 'totalTasks', 'completedTasks', 'overdueTasks', 'dueSoonTasks'));
    }

    /**
     * Vue Kanban des tâches
     */
    private function kanban(Project $project, $tasks, $totalTasks, $completedTasks, $overdueTasks, $dueSoonTasks)
    {
        // Grouper les tâches par statut
        $tasksByStatus = [
            'a_faire' => $tasks->where('status', 'a_faire'),
            'en_cours' => $tasks->where('status', 'en_cours'),
            'termine' => $tasks->where('status', 'termine'),
            'bloque' => $tasks->where('status', 'bloque'),
        ];

        return view('tasks.kanban', compact('project', 'tasks', 'tasksByStatus', 'totalTasks', 'completedTasks', 'overdueTasks', 'dueSoonTasks'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Project $project)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if ($project->company_id !== $companyId) {
            abort(403, 'Accès non autorisé.');
        }

        $employees = $project->employees()->wherePivot('is_active', true)->get();

        return view('tasks.create', compact('project', 'employees'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Project $project)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if ($project->company_id !== $companyId) {
            abort(403, 'Accès non autorisé.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:255',
            'status' => 'required|in:a_faire,en_cours,termine,bloque',
            'priority' => 'required|in:basse,moyenne,haute,urgente',
            'start_date' => 'nullable|date',
            'deadline' => 'nullable|date|after_or_equal:start_date',
            'progress' => 'nullable|integer|min:0|max:100',
            'assigned_to' => 'nullable|exists:employees,id',
            'notes' => 'nullable|string',
        ]);

        // Vérifier que l'employé est affecté au projet
        if ($validated['assigned_to']) {
            $employee = Employee::findOrFail($validated['assigned_to']);
            if (!$employee->isAssignedToProject($project->id)) {
                return redirect()->back()
                    ->with('error', 'L\'employé sélectionné n\'est pas affecté à ce projet.');
            }
        }

        $validated['project_id'] = $project->id;
        $validated['created_by'] = $user->id;
        $validated['progress'] = $validated['progress'] ?? 0;

        $task = Task::create($validated);

        // Notifier les membres du projet (sauf le créateur)
        $projectMembers = $project->company->users()
            ->where('users.id', '!=', $user->id)
            ->pluck('users.id')
            ->toArray();

        foreach ($projectMembers as $memberId) {
            \App\Models\Notification::create([
                'user_id' => $memberId,
                'project_id' => $project->id,
                'type' => 'task_assigned',
                'title' => 'Nouvelle tâche créée',
                'message' => $user->name . ' a créé une nouvelle tâche "' . $task->title . '" sur le projet "' . $project->name . '"',
                'link' => route('projects.show', $project) . '#tasks',
                'data' => [
                    'task_id' => $task->id,
                ],
            ]);
        }

        return redirect()->route('tasks.index', $project)
            ->with('success', 'Tâche créée avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project, Task $task)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if ($project->company_id !== $companyId || $task->project_id !== $project->id) {
            abort(403, 'Accès non autorisé.');
        }

        $task->load('creator', 'assignedEmployee', 'project');

        return view('tasks.show', compact('project', 'task'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Project $project, Task $task)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if ($project->company_id !== $companyId || $task->project_id !== $project->id) {
            abort(403, 'Accès non autorisé.');
        }

        $employees = $project->employees()->wherePivot('is_active', true)->get();

        return view('tasks.edit', compact('project', 'task', 'employees'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Project $project, Task $task)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if ($project->company_id !== $companyId || $task->project_id !== $project->id) {
            abort(403, 'Accès non autorisé.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:255',
            'status' => 'required|in:a_faire,en_cours,termine,bloque',
            'priority' => 'required|in:basse,moyenne,haute,urgente',
            'start_date' => 'nullable|date',
            'deadline' => 'nullable|date|after_or_equal:start_date',
            'progress' => 'nullable|integer|min:0|max:100',
            'assigned_to' => 'nullable|exists:employees,id',
            'notes' => 'nullable|string',
        ]);

        // Vérifier que l'employé est affecté au projet
        if ($validated['assigned_to']) {
            $employee = Employee::findOrFail($validated['assigned_to']);
            if (!$employee->isAssignedToProject($project->id)) {
                return redirect()->back()
                    ->with('error', 'L\'employé sélectionné n\'est pas affecté à ce projet.');
            }
        }

        $oldAssignedTo = $task->assigned_to;
        $task->update($validated);

        // Notifier les membres du projet si la tâche a été modifiée
        if ($task->assigned_to && $task->assigned_to !== $oldAssignedTo) {
            $projectMembers = $project->company->users()
                ->where('users.id', '!=', $user->id)
                ->pluck('users.id')
                ->toArray();

            foreach ($projectMembers as $memberId) {
                \App\Models\Notification::create([
                    'user_id' => $memberId,
                    'project_id' => $project->id,
                    'type' => 'task_assigned',
                    'title' => 'Tâche modifiée',
                    'message' => $user->name . ' a modifié la tâche "' . $task->title . '" sur le projet "' . $project->name . '"',
                    'link' => route('projects.show', $project) . '#tasks',
                    'data' => [
                        'task_id' => $task->id,
                    ],
                ]);
            }
        }

        return redirect()->route('tasks.index', $project)
            ->with('success', 'Tâche mise à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project, Task $task)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if ($project->company_id !== $companyId || $task->project_id !== $project->id) {
            abort(403, 'Accès non autorisé.');
        }

        $task->delete();

        return redirect()->route('tasks.index', $project)
            ->with('success', 'Tâche supprimée avec succès.');
    }
}
