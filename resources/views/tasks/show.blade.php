@extends('layouts.app')

@section('title', $task->title)

@section('content')
<div class="row">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h5 class="card-title fw-semibold mb-0">{{ $task->title }}</h5>
          <div class="d-flex gap-2">
            <a href="{{ route('tasks.edit', ['project' => $project, 'task' => $task]) }}" class="btn btn-warning">
              <i class="ti ti-edit me-2"></i>Modifier
            </a>
            <a href="{{ route('tasks.index', $project) }}" class="btn btn-secondary">Retour</a>
          </div>
        </div>

        @if($task->isOverdue())
          <div class="alert alert-danger mb-4">
            <i class="ti ti-alert-triangle me-2"></i>
            <strong>Attention !</strong> Cette tâche est en retard (deadline: {{ $task->deadline->format('d/m/Y') }})
          </div>
        @elseif($task->isDueSoon())
          <div class="alert alert-warning mb-4">
            <i class="ti ti-clock me-2"></i>
            <strong>Attention !</strong> Cette tâche arrive bientôt à échéance (deadline: {{ $task->deadline->format('d/m/Y') }})
          </div>
        @endif

        <div class="row mb-4">
          <div class="col-md-6">
            <h6 class="fw-semibold mb-2">Statut</h6>
            @php
              $statusColors = [
                'a_faire' => 'secondary',
                'en_cours' => 'primary',
                'termine' => 'success',
                'bloque' => 'danger',
              ];
            @endphp
            <span class="badge bg-{{ $statusColors[$task->status] ?? 'secondary' }} rounded-3 fw-semibold fs-4">
              {{ $task->status_label }}
            </span>
          </div>
          <div class="col-md-6">
            <h6 class="fw-semibold mb-2">Priorité</h6>
            @php
              $priorityColors = [
                'basse' => 'secondary',
                'moyenne' => 'info',
                'haute' => 'warning',
                'urgente' => 'danger',
              ];
            @endphp
            <span class="badge bg-{{ $priorityColors[$task->priority] ?? 'secondary' }} rounded-3 fw-semibold fs-4">
              {{ $task->priority_label }}
            </span>
          </div>
        </div>

        <div class="row mb-4">
          <div class="col-md-6">
            <h6 class="fw-semibold mb-2">Avancement</h6>
            <div class="d-flex align-items-center">
              <div class="progress flex-grow-1 me-2" style="height: 20px;">
                <div class="progress-bar" role="progressbar" style="width: {{ $task->progress }}%" aria-valuenow="{{ $task->progress }}" aria-valuemin="0" aria-valuemax="100">
                  {{ $task->progress }}%
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <h6 class="fw-semibold mb-2">Assigné à</h6>
            @if($task->assignedEmployee)
              <p class="mb-0">{{ $task->assignedEmployee->full_name }}</p>
              @if($task->assignedEmployee->position)
                <p class="mb-0 text-muted">{{ $task->assignedEmployee->position }}</p>
              @endif
            @else
              <span class="text-muted">Non assigné</span>
            @endif
          </div>
        </div>

        @if($task->description)
          <div class="mb-4">
            <h6 class="fw-semibold mb-2">Description</h6>
            <p class="mb-0">{{ $task->description }}</p>
          </div>
        @endif

        <div class="row mb-4">
          <div class="col-md-6">
            <h6 class="fw-semibold mb-2">Catégorie</h6>
            @if($task->category)
              <span class="badge bg-info rounded-3 fw-semibold">{{ $task->category }}</span>
            @else
              <span class="text-muted">Non définie</span>
            @endif
          </div>
          <div class="col-md-6">
            <h6 class="fw-semibold mb-2">Dates</h6>
            @if($task->start_date)
              <p class="mb-1"><strong>Début:</strong> {{ $task->start_date->format('d/m/Y') }}</p>
            @endif
            @if($task->deadline)
              <p class="mb-0">
                <strong>Deadline:</strong> 
                <span class="{{ $task->isOverdue() ? 'text-danger fw-bold' : ($task->isDueSoon() ? 'text-warning' : '') }}">
                  {{ $task->deadline->format('d/m/Y') }}
                </span>
              </p>
            @endif
          </div>
        </div>

        @if($task->notes)
          <div class="mb-4">
            <h6 class="fw-semibold mb-2">Notes</h6>
            <p class="mb-0">{{ $task->notes }}</p>
          </div>
        @endif

        <div class="mb-4">
          <h6 class="fw-semibold mb-2">Informations</h6>
          <p class="mb-1"><strong>Créé par:</strong> {{ $task->creator->name ?? 'N/A' }}</p>
          <p class="mb-0"><strong>Créé le:</strong> {{ $task->created_at->format('d/m/Y à H:i') }}</p>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

