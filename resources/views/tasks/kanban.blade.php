@extends('layouts.app')

@section('title', 'Kanban des tâches - ' . $project->name)

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h5 class="card-title fw-semibold mb-0">Kanban des tâches - {{ $project->name }}</h5>
          <div class="d-flex gap-2">
            <a href="{{ route('tasks.index', ['project' => $project, 'view' => 'list']) }}" class="btn btn-sm btn-outline-primary">Liste</a>
            <a href="{{ route('tasks.index', ['project' => $project, 'view' => 'calendar']) }}" class="btn btn-sm btn-outline-primary">Calendrier</a>
            <a href="{{ route('tasks.create', $project) }}" class="btn btn-primary">
              <i class="ti ti-plus me-2"></i>Nouvelle tâche
            </a>
            <a href="{{ route('projects.show', $project) }}" class="btn btn-secondary">Retour</a>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-3">
            <div class="card bg-primary text-white">
              <div class="card-body">
                <h6 class="text-white">Total</h6>
                <h4 class="text-white mb-0">{{ $totalTasks }}</h4>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card bg-success text-white">
              <div class="card-body">
                <h6 class="text-white">Terminées</h6>
                <h4 class="text-white mb-0">{{ $completedTasks }}</h4>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card bg-danger text-white">
              <div class="card-body">
                <h6 class="text-white">En retard</h6>
                <h4 class="text-white mb-0">{{ $overdueTasks }}</h4>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card bg-warning text-white">
              <div class="card-body">
                <h6 class="text-white">Échéance proche</h6>
                <h4 class="text-white mb-0">{{ $dueSoonTasks }}</h4>
              </div>
            </div>
          </div>
        </div>

        <div class="kanban-board">
          <div class="row">
            <div class="col-md-3">
              <div class="card">
                <div class="card-header bg-secondary text-white">
                  <h6 class="fw-semibold mb-0 text-white">À faire</h6>
                  <span class="badge bg-light text-dark">{{ $tasksByStatus['a_faire']->count() }}</span>
                </div>
                <div class="card-body" style="min-height: 400px; max-height: 600px; overflow-y: auto;">
                  @foreach($tasksByStatus['a_faire'] as $task)
                    <div class="card mb-2 task-card" data-task-id="{{ $task->id }}">
                      <div class="card-body p-2">
                        <h6 class="fw-semibold mb-1" style="font-size: 0.9rem;">{{ $task->title }}</h6>
                        @if($task->description)
                          <p class="mb-1 text-muted" style="font-size: 0.8rem;">{{ Str::limit($task->description, 50) }}</p>
                        @endif
                        <div class="d-flex gap-1 flex-wrap mb-1">
                          @if($task->priority)
                            <span class="badge bg-{{ $task->priority === 'urgente' ? 'danger' : ($task->priority === 'haute' ? 'warning' : 'info') }}" style="font-size: 0.7rem;">
                              {{ ucfirst($task->priority) }}
                            </span>
                          @endif
                          @if($task->isOverdue())
                            <span class="badge bg-danger" style="font-size: 0.7rem;">En retard</span>
                          @endif
                        </div>
                        @if($task->due_date)
                          <small class="text-muted">
                            <i class="ti ti-calendar"></i> {{ $task->due_date->format('d/m/Y') }}
                          </small>
                        @endif
                        <div class="mt-2">
                          <a href="{{ route('tasks.show', ['project' => $project, 'task' => $task]) }}" class="btn btn-sm btn-outline-primary" style="font-size: 0.75rem;">Voir</a>
                        </div>
                      </div>
                    </div>
                  @endforeach
                  @if($tasksByStatus['a_faire']->count() === 0)
                    <p class="text-muted text-center mt-3">Aucune tâche</p>
                  @endif
                </div>
              </div>
            </div>

            <div class="col-md-3">
              <div class="card">
                <div class="card-header bg-primary text-white">
                  <h6 class="fw-semibold mb-0 text-white">En cours</h6>
                  <span class="badge bg-light text-dark">{{ $tasksByStatus['en_cours']->count() }}</span>
                </div>
                <div class="card-body" style="min-height: 400px; max-height: 600px; overflow-y: auto;">
                  @foreach($tasksByStatus['en_cours'] as $task)
                    <div class="card mb-2 task-card" data-task-id="{{ $task->id }}">
                      <div class="card-body p-2">
                        <h6 class="fw-semibold mb-1" style="font-size: 0.9rem;">{{ $task->title }}</h6>
                        @if($task->completion_percentage > 0)
                          <div class="progress mb-1" style="height: 4px;">
                            <div class="progress-bar" style="width: {{ $task->completion_percentage }}%"></div>
                          </div>
                          <small class="text-muted">{{ $task->completion_percentage }}%</small>
                        @endif
                        @if($task->assignedEmployee)
                          <div class="mt-1">
                            <small class="text-muted">
                              <i class="ti ti-user"></i> {{ $task->assignedEmployee->fullName }}
                            </small>
                          </div>
                        @endif
                        <div class="mt-2">
                          <a href="{{ route('tasks.show', ['project' => $project, 'task' => $task]) }}" class="btn btn-sm btn-outline-primary" style="font-size: 0.75rem;">Voir</a>
                        </div>
                      </div>
                    </div>
                  @endforeach
                  @if($tasksByStatus['en_cours']->count() === 0)
                    <p class="text-muted text-center mt-3">Aucune tâche</p>
                  @endif
                </div>
              </div>
            </div>

            <div class="col-md-3">
              <div class="card">
                <div class="card-header bg-success text-white">
                  <h6 class="fw-semibold mb-0 text-white">Terminé</h6>
                  <span class="badge bg-light text-dark">{{ $tasksByStatus['termine']->count() }}</span>
                </div>
                <div class="card-body" style="min-height: 400px; max-height: 600px; overflow-y: auto;">
                  @foreach($tasksByStatus['termine'] as $task)
                    <div class="card mb-2 task-card bg-light" data-task-id="{{ $task->id }}">
                      <div class="card-body p-2">
                        <h6 class="fw-semibold mb-1" style="font-size: 0.9rem; text-decoration: line-through;">{{ $task->title }}</h6>
                        <small class="text-muted">
                          <i class="ti ti-check"></i> Terminé
                        </small>
                        <div class="mt-2">
                          <a href="{{ route('tasks.show', ['project' => $project, 'task' => $task]) }}" class="btn btn-sm btn-outline-primary" style="font-size: 0.75rem;">Voir</a>
                        </div>
                      </div>
                    </div>
                  @endforeach
                  @if($tasksByStatus['termine']->count() === 0)
                    <p class="text-muted text-center mt-3">Aucune tâche</p>
                  @endif
                </div>
              </div>
            </div>

            <div class="col-md-3">
              <div class="card">
                <div class="card-header bg-danger text-white">
                  <h6 class="fw-semibold mb-0 text-white">Bloqué</h6>
                  <span class="badge bg-light text-dark">{{ $tasksByStatus['bloque']->count() }}</span>
                </div>
                <div class="card-body" style="min-height: 400px; max-height: 600px; overflow-y: auto;">
                  @foreach($tasksByStatus['bloque'] as $task)
                    <div class="card mb-2 task-card border-danger" data-task-id="{{ $task->id }}">
                      <div class="card-body p-2">
                        <h6 class="fw-semibold mb-1" style="font-size: 0.9rem;">{{ $task->title }}</h6>
                        @if($task->notes)
                          <p class="mb-1 text-danger" style="font-size: 0.8rem;">{{ Str::limit($task->notes, 50) }}</p>
                        @endif
                        <div class="mt-2">
                          <a href="{{ route('tasks.show', ['project' => $project, 'task' => $task]) }}" class="btn btn-sm btn-outline-primary" style="font-size: 0.75rem;">Voir</a>
                        </div>
                      </div>
                    </div>
                  @endforeach
                  @if($tasksByStatus['bloque']->count() === 0)
                    <p class="text-muted text-center mt-3">Aucune tâche</p>
                  @endif
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
.task-card {
  cursor: move;
  transition: all 0.3s ease;
}

.task-card:hover {
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  transform: translateY(-2px);
}
</style>
@endsection

