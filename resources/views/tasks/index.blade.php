@extends('layouts.app')

@section('title', 'Tâches - ' . $project->name)

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h5 class="card-title fw-semibold mb-0">Tâches - {{ $project->name }}</h5>
          <div class="d-flex gap-2">
            <a href="{{ route('tasks.index', ['project' => $project, 'view' => 'calendar']) }}" class="btn btn-sm btn-outline-primary">Calendrier</a>
            <a href="{{ route('tasks.index', ['project' => $project, 'view' => 'kanban']) }}" class="btn btn-sm btn-outline-primary">Kanban</a>
            <a href="{{ route('tasks.create', $project) }}" class="btn btn-primary">
              <i class="ti ti-plus me-2"></i>Nouvelle tâche
            </a>
            <a href="{{ route('projects.show', $project) }}" class="btn btn-secondary">Retour</a>
          </div>
        </div>

        @if(session('success'))
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        @endif

        @if(session('error'))
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        @endif

        <!-- Statistiques -->
        <div class="row mb-4">
          <div class="col-md-3">
            <div class="card bg-light-primary">
              <div class="card-body text-center">
                <h3 class="mb-0">{{ $totalTasks }}</h3>
                <p class="mb-0 text-muted">Total tâches</p>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card bg-light-success">
              <div class="card-body text-center">
                <h3 class="mb-0">{{ $completedTasks }}</h3>
                <p class="mb-0 text-muted">Terminées</p>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card bg-light-danger">
              <div class="card-body text-center">
                <h3 class="mb-0">{{ $overdueTasks }}</h3>
                <p class="mb-0 text-muted">En retard</p>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card bg-light-warning">
              <div class="card-body text-center">
                <h3 class="mb-0">{{ $dueSoonTasks }}</h3>
                <p class="mb-0 text-muted">Échéance proche</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Filtres -->
        <form method="GET" action="{{ route('tasks.index', $project) }}" class="mb-4">
          <div class="row">
            <div class="col-md-4 mb-3">
              <label for="status" class="form-label">Statut</label>
              <select class="form-select" id="status" name="status">
                <option value="">Tous les statuts</option>
                <option value="a_faire" {{ $status == 'a_faire' ? 'selected' : '' }}>À faire</option>
                <option value="en_cours" {{ $status == 'en_cours' ? 'selected' : '' }}>En cours</option>
                <option value="termine" {{ $status == 'termine' ? 'selected' : '' }}>Terminé</option>
                <option value="bloque" {{ $status == 'bloque' ? 'selected' : '' }}>Bloqué</option>
              </select>
            </div>
            <div class="col-md-4 mb-3 d-flex align-items-end">
              <button type="submit" class="btn btn-primary w-100">Filtrer</button>
            </div>
          </div>
        </form>

        <div class="table-responsive">
          <table class="table text-nowrap mb-0 align-middle">
            <thead class="text-dark fs-4">
              <tr>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Tâche</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Catégorie</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Assigné à</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Priorité</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Échéance</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Avancement</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Statut</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Actions</h6>
                </th>
              </tr>
            </thead>
            <tbody>
              @forelse($tasks as $task)
                <tr class="{{ $task->isOverdue() ? 'table-danger' : ($task->isDueSoon() ? 'table-warning' : '') }}">
                  <td class="border-bottom-0">
                    <h6 class="fw-semibold mb-0">{{ $task->title }}</h6>
                    @if($task->description)
                      <p class="mb-0 fw-normal text-muted" style="font-size: 0.85rem;">{{ Str::limit($task->description, 50) }}</p>
                    @endif
                  </td>
                  <td class="border-bottom-0">
                    @if($task->category)
                      <span class="badge bg-info rounded-3 fw-semibold">{{ $task->category }}</span>
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>
                  <td class="border-bottom-0">
                    @if($task->assignedEmployee)
                      <p class="mb-0 fw-normal">{{ $task->assignedEmployee->full_name }}</p>
                    @else
                      <span class="text-muted">Non assigné</span>
                    @endif
                  </td>
                  <td class="border-bottom-0">
                    @php
                      $priorityColors = [
                        'basse' => 'secondary',
                        'moyenne' => 'info',
                        'haute' => 'warning',
                        'urgente' => 'danger',
                      ];
                    @endphp
                    <span class="badge bg-{{ $priorityColors[$task->priority] ?? 'secondary' }} rounded-3 fw-semibold">
                      {{ $task->priority_label }}
                    </span>
                  </td>
                  <td class="border-bottom-0">
                    @if($task->deadline)
                      <p class="mb-0 fw-normal {{ $task->isOverdue() ? 'text-danger fw-bold' : ($task->isDueSoon() ? 'text-warning' : '') }}">
                        {{ $task->deadline->format('d/m/Y') }}
                      </p>
                      @if($task->isOverdue())
                        <small class="text-danger"><i class="ti ti-alert-triangle"></i> En retard</small>
                      @elseif($task->isDueSoon())
                        <small class="text-warning"><i class="ti ti-clock"></i> Bientôt</small>
                      @endif
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>
                  <td class="border-bottom-0">
                    <div class="d-flex align-items-center">
                      <div class="progress" style="width: 100px; height: 8px;">
                        <div class="progress-bar" role="progressbar" style="width: {{ $task->progress }}%" aria-valuenow="{{ $task->progress }}" aria-valuemin="0" aria-valuemax="100"></div>
                      </div>
                      <span class="ms-2">{{ $task->progress }}%</span>
                    </div>
                  </td>
                  <td class="border-bottom-0">
                    @php
                      $statusColors = [
                        'a_faire' => 'secondary',
                        'en_cours' => 'primary',
                        'termine' => 'success',
                        'bloque' => 'danger',
                      ];
                    @endphp
                    <span class="badge bg-{{ $statusColors[$task->status] ?? 'secondary' }} rounded-3 fw-semibold">
                      {{ $task->status_label }}
                    </span>
                  </td>
                  <td class="border-bottom-0">
                    <div class="d-flex align-items-center gap-2">
                      <a href="{{ route('tasks.show', ['project' => $project, 'task' => $task]) }}" class="btn btn-sm btn-info">Voir</a>
                      <a href="{{ route('tasks.edit', ['project' => $project, 'task' => $task]) }}" class="btn btn-sm btn-warning">Modifier</a>
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="8" class="text-center py-4">
                    <p class="mb-0">Aucune tâche trouvée. <a href="{{ route('tasks.create', $project) }}">Créer une tâche</a></p>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

