@extends('layouts.app')

@section('title', 'Calendrier des tâches - ' . $project->name)

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h5 class="card-title fw-semibold mb-0">Calendrier des tâches - {{ $project->name }}</h5>
          <div class="d-flex gap-2">
            <a href="{{ route('tasks.index', ['project' => $project, 'view' => 'list']) }}" class="btn btn-sm btn-outline-primary">Liste</a>
            <a href="{{ route('tasks.index', ['project' => $project, 'view' => 'kanban']) }}" class="btn btn-sm btn-outline-primary">Kanban</a>
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

        <div class="calendar-view">
          @foreach($tasksByDate as $date => $dateTasks)
            @if($date !== 'no-date')
              <div class="card mb-3">
                <div class="card-header">
                  <h6 class="fw-semibold mb-0">
                    {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}
                    <span class="badge bg-primary ms-2">{{ $dateTasks->count() }} tâche(s)</span>
                  </h6>
                </div>
                <div class="card-body">
                  <div class="row">
                    @foreach($dateTasks as $task)
                      <div class="col-md-6 mb-3">
                        <div class="card border-start border-{{ $task->isOverdue() ? 'danger' : ($task->isDueSoon() ? 'warning' : 'primary') }} border-3">
                          <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                              <h6 class="fw-semibold mb-0">{{ $task->title }}</h6>
                              <span class="badge bg-{{ $task->status === 'termine' ? 'success' : ($task->status === 'en_cours' ? 'primary' : 'secondary') }}">
                                {{ ucfirst($task->status) }}
                              </span>
                            </div>
                            @if($task->description)
                              <p class="mb-2 text-muted" style="font-size: 0.9rem;">{{ Str::limit($task->description, 100) }}</p>
                            @endif
                            <div class="d-flex gap-2 flex-wrap">
                              @if($task->priority)
                                <span class="badge bg-{{ $task->priority === 'urgente' ? 'danger' : ($task->priority === 'haute' ? 'warning' : 'info') }}">
                                  {{ ucfirst($task->priority) }}
                                </span>
                              @endif
                              @if($task->assignedEmployee)
                                <span class="badge bg-secondary">{{ $task->assignedEmployee->fullName }}</span>
                              @endif
                              @if($task->completion_percentage > 0)
                                <span class="badge bg-info">{{ $task->completion_percentage }}%</span>
                              @endif
                            </div>
                            <div class="mt-2">
                              <a href="{{ route('tasks.show', ['project' => $project, 'task' => $task]) }}" class="btn btn-sm btn-outline-primary">Voir</a>
                            </div>
                          </div>
                        </div>
                      </div>
                    @endforeach
                  </div>
                </div>
              </div>
            @endif
          @endforeach

          @if(isset($tasksByDate['no-date']))
            <div class="card mb-3">
              <div class="card-header">
                <h6 class="fw-semibold mb-0">Sans date d'échéance</h6>
              </div>
              <div class="card-body">
                <div class="row">
                  @foreach($tasksByDate['no-date'] as $task)
                    <div class="col-md-6 mb-3">
                      <div class="card">
                        <div class="card-body">
                          <h6 class="fw-semibold mb-0">{{ $task->title }}</h6>
                          <span class="badge bg-secondary">{{ ucfirst($task->status) }}</span>
                        </div>
                      </div>
                    </div>
                  @endforeach
                </div>
              </div>
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

