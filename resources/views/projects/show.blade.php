@extends('layouts.app')

@section('title', $project->name)

@section('content')
<div class="row">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h5 class="card-title fw-semibold mb-0">{{ $project->name }}</h5>
          <div class="d-flex gap-2">
            @if(auth()->user()->hasPermission('projects.update'))
              <a href="{{ route('projects.edit', $project) }}" class="btn btn-warning">
                <i class="ti ti-edit me-2"></i>Modifier
              </a>
            @endif
            <a href="{{ route('projects.index') }}" class="btn btn-secondary">Retour</a>
          </div>
        </div>

        @if(session('success'))
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        @endif

        <div class="row mb-4">
          <div class="col-md-6">
            <h6 class="fw-semibold mb-2">Statut</h6>
            @php
              $statusColors = [
                'non_demarre' => 'secondary',
                'en_cours' => 'primary',
                'termine' => 'success',
                'bloque' => 'danger',
              ];
              $statusLabels = [
                'non_demarre' => 'Non démarré',
                'en_cours' => 'En cours',
                'termine' => 'Terminé',
                'bloque' => 'Bloqué',
              ];
            @endphp
            <span class="badge bg-{{ $statusColors[$project->status] ?? 'secondary' }} rounded-3 fw-semibold fs-4">
              {{ $statusLabels[$project->status] ?? $project->status }}
            </span>
          </div>
          <div class="col-md-6">
            <h6 class="fw-semibold mb-2">Avancement</h6>
            <div class="d-flex align-items-center">
              <div class="progress flex-grow-1 me-2" style="height: 20px;">
                <div class="progress-bar" role="progressbar" style="width: {{ $project->progress }}%" aria-valuenow="{{ $project->progress }}" aria-valuemin="0" aria-valuemax="100">
                  {{ $project->progress }}%
                </div>
              </div>
            </div>
          </div>
        </div>

        @if($project->description)
          <div class="mb-4">
            <h6 class="fw-semibold mb-2">Description</h6>
            <p class="mb-0">{{ $project->description }}</p>
          </div>
        @endif

        <div class="row mb-4">
          <div class="col-md-4">
            <h6 class="fw-semibold mb-2">Budget</h6>
            <p class="fs-4 fw-semibold mb-0">{{ number_format($project->budget, 2, ',', ' ') }} FCFA</p>
            @if(isset($totalExpenses))
              <small class="text-muted">Dépensé: {{ number_format($totalExpenses, 2, ',', ' ') }} FCFA ({{ number_format($budgetUsed, 1) }}%)</small>
              <div class="progress mt-1" style="height: 8px;">
                <div class="progress-bar {{ $budgetUsed > 100 ? 'bg-danger' : ($budgetUsed > 80 ? 'bg-warning' : 'bg-success') }}" style="width: {{ min($budgetUsed, 100) }}%"></div>
              </div>
            @endif
          </div>
          <div class="col-md-4">
            <h6 class="fw-semibold mb-2">Dépenses</h6>
            @if(isset($totalExpenses))
              <p class="fs-4 fw-semibold mb-0 text-info">{{ number_format($totalExpenses, 2, ',', ' ') }} FCFA</p>
              <small class="text-muted">Payé: {{ number_format($paidExpenses, 2, ',', ' ') }} FCFA | En attente: {{ number_format($unpaidExpenses, 2, ',', ' ') }} FCFA</small>
            @else
              <p class="mb-0">Aucune dépense</p>
            @endif
          </div>
          <div class="col-md-4">
            <h6 class="fw-semibold mb-2">Dates</h6>
            @if($project->start_date)
              <p class="mb-1"><strong>Début:</strong> {{ $project->start_date->format('d/m/Y') }}</p>
            @endif
            @if($project->end_date)
              <p class="mb-0"><strong>Fin prévue:</strong> {{ $project->end_date->format('d/m/Y') }}</p>
            @endif
          </div>
        </div>

        <!-- Graphique d'évolution de l'avancement -->
        @if(isset($progressChartData) && count($progressChartData) > 0)
          <div class="card mb-4">
            <div class="card-body">
              <h6 class="fw-semibold mb-3">Évolution de l'avancement</h6>
              <canvas id="progressEvolutionChart" height="100"></canvas>
            </div>
          </div>
        @endif

        @if(isset($expensesByType) && count($expensesByType) > 0)
          <div class="row mb-4">
            <div class="col-md-6">
              <div class="card">
                <div class="card-body">
                  <h6 class="fw-semibold mb-3">Dépenses par type</h6>
                  <canvas id="expensesByTypeChart" height="200"></canvas>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="card">
                <div class="card-body">
                  <h6 class="fw-semibold mb-3">Évolution des dépenses</h6>
                  <canvas id="expensesByMonthChart" height="200"></canvas>
                </div>
              </div>
            </div>
          </div>
        @endif

        @if($project->address)
          <div class="mb-4">
            <h6 class="fw-semibold mb-2">Adresse</h6>
            <p class="mb-0">{{ $project->address }}</p>
            @if($project->latitude && $project->longitude)
              <small class="text-muted">GPS: {{ $project->latitude }}, {{ $project->longitude }}</small>
            @endif
          </div>
        @endif

        @if($project->client_name)
          <div class="mb-4">
            <h6 class="fw-semibold mb-2">Client</h6>
            <p class="mb-0"><strong>{{ $project->client_name }}</strong></p>
            @if($project->client_contact)
              <p class="mb-0 text-muted">{{ $project->client_contact }}</p>
            @endif
          </div>
        @endif

        <div class="mb-4">
          <h6 class="fw-semibold mb-2">Informations</h6>
          <p class="mb-1"><strong>Créé par:</strong> {{ $project->creator->name ?? 'N/A' }}</p>
          <p class="mb-0"><strong>Créé le:</strong> {{ $project->created_at->format('d/m/Y à H:i') }}</p>
        </div>

        <hr class="my-4">

        <div class="d-flex justify-content-between align-items-center mb-3">
          <h6 class="fw-semibold mb-0">Matériaux du projet</h6>
          <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addMaterialModal">
            <i class="ti ti-plus me-1"></i>Ajouter un matériau
          </button>
        </div>

        @if($project->materials->count() > 0)
          <div class="table-responsive">
            <table class="table text-nowrap mb-0 align-middle">
              <thead class="text-dark fs-4">
                <tr>
                  <th class="border-bottom-0">
                    <h6 class="fw-semibold mb-0">Matériau</h6>
                  </th>
                  <th class="border-bottom-0">
                    <h6 class="fw-semibold mb-0">Prévu</h6>
                  </th>
                  <th class="border-bottom-0">
                    <h6 class="fw-semibold mb-0">Commandé</h6>
                  </th>
                  <th class="border-bottom-0">
                    <h6 class="fw-semibold mb-0">Livré</h6>
                  </th>
                  <th class="border-bottom-0">
                    <h6 class="fw-semibold mb-0">Utilisé</h6>
                  </th>
                  <th class="border-bottom-0">
                    <h6 class="fw-semibold mb-0">Restant</h6>
                  </th>
                  <th class="border-bottom-0">
                    <h6 class="fw-semibold mb-0">Actions</h6>
                  </th>
                </tr>
              </thead>
              <tbody>
                @foreach($project->materials as $material)
                  @php
                    $pivot = $material->pivot;
                  @endphp
                  <tr>
                    <td class="border-bottom-0">
                      <h6 class="fw-semibold mb-0">{{ $material->name }}</h6>
                      @if($material->category)
                        <span class="badge bg-info" style="font-size: 0.7rem;">{{ $material->category }}</span>
                      @endif
                    </td>
                    <td class="border-bottom-0">
                      {{ number_format($pivot->quantity_planned, 2, ',', ' ') }} {{ $material->unit }}
                    </td>
                    <td class="border-bottom-0">
                      {{ number_format($pivot->quantity_ordered, 2, ',', ' ') }} {{ $material->unit }}
                    </td>
                    <td class="border-bottom-0">
                      {{ number_format($pivot->quantity_delivered, 2, ',', ' ') }} {{ $material->unit }}
                    </td>
                    <td class="border-bottom-0">
                      @php
                        $isOverConsumption = $pivot->quantity_used > $pivot->quantity_planned;
                      @endphp
                      <span class="{{ $isOverConsumption ? 'text-danger fw-bold' : '' }}">
                        {{ number_format($pivot->quantity_used, 2, ',', ' ') }} {{ $material->unit }}
                      </span>
                      @if($isOverConsumption)
                        <i class="ti ti-alert-triangle text-danger ms-1" title="Surconsommation"></i>
                      @endif
                    </td>
                    <td class="border-bottom-0">
                      <span class="{{ $pivot->quantity_remaining < ($pivot->quantity_planned * 0.1) ? 'text-warning' : '' }}">
                        {{ number_format($pivot->quantity_remaining, 2, ',', ' ') }} {{ $material->unit }}
                      </span>
                    </td>
                    <td class="border-bottom-0">
                      <div class="d-flex gap-2">
                        @if(auth()->user()->hasPermission('materials.manage') || auth()->user()->hasRoleInCompany('admin'))
                          @if($pivot->quantity_remaining > 0)
                            <a href="{{ route('projects.materials.transfer', ['project' => $project, 'material' => $material]) }}" class="btn btn-sm btn-info" title="Transférer vers un autre projet">
                              <i class="ti ti-arrow-right"></i>
                            </a>
                          @endif
                          <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#updateMaterialModal{{ $material->id }}">
                            <i class="ti ti-edit"></i>
                          </button>
                        @endif
                      </div>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @else
          <p class="text-muted">Aucun matériau associé à ce projet.</p>
        @endif

        <hr class="my-4">

        <div class="d-flex justify-content-between align-items-center mb-3">
          <h6 class="fw-semibold mb-0">Employés affectés</h6>
          <div class="d-flex gap-2">
            @if(auth()->user()->hasPermission('projects.manage_team') || auth()->user()->hasRoleInCompany('admin'))
              <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#assignEmployeeModal">
                <i class="ti ti-plus me-1"></i>Affecter un employé
              </button>
            @endif
            @if(auth()->user()->hasPermission('checkin.create') || auth()->user()->hasRoleInCompany('admin'))
              <a href="{{ route('attendances.index', $project) }}" class="btn btn-sm btn-info">
                <i class="ti ti-clock me-1"></i>Pointage
              </a>
            @endif
          </div>
        </div>

        @if($project->employees->count() > 0)
          <div class="table-responsive mb-4">
            <table class="table text-nowrap mb-0 align-middle">
              <thead class="text-dark fs-4">
                <tr>
                  <th class="border-bottom-0">
                    <h6 class="fw-semibold mb-0">Employé</h6>
                  </th>
                  <th class="border-bottom-0">
                    <h6 class="fw-semibold mb-0">Poste</h6>
                  </th>
                  <th class="border-bottom-0">
                    <h6 class="fw-semibold mb-0">Date d'affectation</h6>
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
                @foreach($project->employees as $employee)
                  <tr>
                    <td class="border-bottom-0">
                      <h6 class="fw-semibold mb-0">{{ $employee->full_name }}</h6>
                    </td>
                    <td class="border-bottom-0">
                      @if($employee->position)
                        <span class="badge bg-info">{{ $employee->position }}</span>
                      @else
                        <span class="text-muted">-</span>
                      @endif
                    </td>
                    <td class="border-bottom-0">
                      @if($employee->pivot->assigned_date)
                        {{ \Carbon\Carbon::parse($employee->pivot->assigned_date)->format('d/m/Y') }}
                      @else
                        <span class="text-muted">-</span>
                      @endif
                    </td>
                    <td class="border-bottom-0">
                      @if($employee->pivot->is_active)
                        <span class="badge bg-success">Actif</span>
                      @else
                        <span class="badge bg-secondary">Inactif</span>
                      @endif
                    </td>
                    <td class="border-bottom-0">
                      @if($employee->pivot->is_active)
                        <form action="{{ route('projects.employees.remove', ['project' => $project, 'employee' => $employee]) }}" method="POST" onsubmit="return confirm('Retirer cet employé du projet ?');" class="d-inline">
                          @csrf
                          <button type="submit" class="btn btn-sm btn-danger">
                            <i class="ti ti-user-minus"></i>
                          </button>
                        </form>
                      @endif
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @else
          <p class="text-muted mb-4">Aucun employé affecté à ce projet.</p>
        @endif

        <hr class="my-4">

        <div class="d-flex gap-2 flex-wrap">
          @if(auth()->user()->hasPermission('projects.view'))
            <a href="{{ route('projects.timeline', $project) }}" class="btn btn-outline-primary">
              <i class="ti ti-timeline me-2"></i>Timeline
            </a>
          @endif
          
          <!-- Historique des changements de statut -->
          @if($project->statusHistory && $project->statusHistory->count() > 0)
            <div class="card mt-4">
              <div class="card-body">
                <h6 class="fw-semibold mb-3">Historique des changements de statut</h6>
                <div class="timeline">
                  @foreach($project->statusHistory as $history)
                    <div class="d-flex mb-3">
                      <div class="flex-shrink-0">
                        <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                          <i class="ti ti-arrow-right"></i>
                        </div>
                      </div>
                      <div class="flex-grow-1 ms-3">
                        <div class="d-flex justify-content-between align-items-start">
                          <div>
                            <h6 class="mb-1 fw-semibold">
                              @php
                                $statusLabels = [
                                  'non_demarre' => 'Non démarré',
                                  'en_cours' => 'En cours',
                                  'termine' => 'Terminé',
                                  'bloque' => 'Bloqué',
                                ];
                                $statusColors = [
                                  'non_demarre' => 'secondary',
                                  'en_cours' => 'primary',
                                  'termine' => 'success',
                                  'bloque' => 'danger',
                                ];
                              @endphp
                              <span class="badge bg-{{ $statusColors[$history->old_status] ?? 'secondary' }} me-1">
                                {{ $history->old_status ? ($statusLabels[$history->old_status] ?? $history->old_status) : 'N/A' }}
                              </span>
                              <i class="ti ti-arrow-right"></i>
                              <span class="badge bg-{{ $statusColors[$history->new_status] ?? 'primary' }}">
                                {{ $statusLabels[$history->new_status] ?? $history->new_status }}
                              </span>
                            </h6>
                            @if($history->reason)
                              <p class="mb-1 text-muted">{{ $history->reason }}</p>
                            @endif
                            <small class="text-muted">
                              Par {{ $history->user->name }} le {{ $history->created_at->format('d/m/Y à H:i') }}
                            </small>
                          </div>
                        </div>
                      </div>
                    </div>
                  @endforeach
                </div>
              </div>
            </div>
          @endif
          @if(auth()->user()->hasPermission('projects.view'))
            <a href="{{ route('projects.gallery', $project) }}" class="btn btn-outline-info">
              <i class="ti ti-photo me-2"></i>Galerie
            </a>
          @endif
          @if(auth()->user()->hasPermission('progress.update') || auth()->user()->hasPermission('progress.view'))
            <a href="{{ route('progress.index', $project) }}" class="btn btn-primary">
              <i class="ti ti-progress me-2"></i>Mises à jour
            </a>
          @endif
          @if(auth()->user()->hasPermission('tasks.view') || auth()->user()->hasPermission('tasks.manage'))
            <a href="{{ route('tasks.index', $project) }}" class="btn btn-success">
              <i class="ti ti-checklist me-2"></i>Tâches
            </a>
          @endif
          @if(auth()->user()->hasPermission('expenses.view') || auth()->user()->hasRoleInCompany('admin') || auth()->user()->hasRoleInCompany('comptable'))
            <a href="{{ route('expenses.index', $project) }}" class="btn btn-info">
              <i class="ti ti-currency-euro me-2"></i>Dépenses
            </a>
          @endif
          @if(auth()->user()->hasPermission('checkin.create') || auth()->user()->hasRoleInCompany('admin'))
            <a href="{{ route('attendances.index', $project) }}" class="btn btn-warning">
              <i class="ti ti-clock me-2"></i>Pointage
            </a>
          @endif
          @if(auth()->user()->hasPermission('reports.view') || auth()->user()->hasPermission('reports.generate'))
            <a href="{{ route('reports.index', $project) }}" class="btn btn-secondary">
              <i class="ti ti-file-text me-2"></i>Rapports
            </a>
          @endif
          @if(auth()->user()->hasPermission('projects.view'))
            <a href="{{ route('comments.index', $project) }}" class="btn btn-dark">
              <i class="ti ti-message-circle me-2"></i>Chat
            </a>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Ajouter Matériau -->
<div class="modal fade" id="addMaterialModal" tabindex="-1" aria-labelledby="addMaterialModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addMaterialModalLabel">Ajouter un matériau au projet</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('projects.materials.add', $project) }}" method="POST">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label for="material_id" class="form-label">Matériau <span class="text-danger">*</span></label>
            <select class="form-select @error('material_id') is-invalid @enderror" id="material_id" name="material_id" required>
              <option value="">Sélectionner un matériau</option>
              @foreach(\App\Models\Material::forCompany(auth()->user()->current_company_id)->active()->get() as $material)
                @if(!$project->materials->contains($material->id))
                  <option value="{{ $material->id }}">{{ $material->name }} ({{ $material->category ?? 'N/A' }})</option>
                @endif
              @endforeach
            </select>
            @error('material_id')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label for="quantity_planned" class="form-label">Quantité prévue <span class="text-danger">*</span></label>
            <input type="number" step="0.01" min="0" class="form-control @error('quantity_planned') is-invalid @enderror" id="quantity_planned" name="quantity_planned" value="{{ old('quantity_planned') }}" required>
            @error('quantity_planned')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label for="notes" class="form-label">Notes</label>
            <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
            @error('notes')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary">Ajouter</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modals Mettre à jour Matériau -->
@foreach($project->materials as $material)
  <div class="modal fade" id="updateMaterialModal{{ $material->id }}" tabindex="-1" aria-labelledby="updateMaterialModalLabel{{ $material->id }}" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="updateMaterialModalLabel{{ $material->id }}">Mettre à jour: {{ $material->name }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form action="{{ route('projects.materials.update', ['project' => $project, 'material' => $material]) }}" method="POST">
          @csrf
          @method('PUT')
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Quantité prévue</label>
              <input type="text" class="form-control" value="{{ number_format($material->pivot->quantity_planned, 2, ',', ' ') }} {{ $material->unit }}" disabled>
            </div>

            <div class="mb-3">
              <label for="quantity_ordered{{ $material->id }}" class="form-label">Quantité commandée</label>
              <input type="number" step="0.01" min="0" class="form-control" id="quantity_ordered{{ $material->id }}" name="quantity_ordered" value="{{ $material->pivot->quantity_ordered }}">
            </div>

            <div class="mb-3">
              <label for="quantity_delivered{{ $material->id }}" class="form-label">Quantité livrée</label>
              <input type="number" step="0.01" min="0" class="form-control" id="quantity_delivered{{ $material->id }}" name="quantity_delivered" value="{{ $material->pivot->quantity_delivered }}">
            </div>

            <div class="mb-3">
              <label for="quantity_used{{ $material->id }}" class="form-label">Quantité utilisée</label>
              @php
                $isOverConsumption = $material->pivot->quantity_used > $material->pivot->quantity_planned;
              @endphp
              <input type="number" step="0.01" min="0" class="form-control @if($isOverConsumption) border-danger @endif" id="quantity_used{{ $material->id }}" name="quantity_used" value="{{ $material->pivot->quantity_used }}">
              @if($isOverConsumption)
                <small class="text-danger">Surconsommation détectée</small>
              @endif
            </div>

            <div class="mb-3">
              <label for="notes{{ $material->id }}" class="form-label">Notes</label>
              <textarea class="form-control" id="notes{{ $material->id }}" name="notes" rows="3">{{ $material->pivot->notes }}</textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
            <button type="submit" class="btn btn-primary">Mettre à jour</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endforeach

<!-- Modal Affecter Employé -->
<div class="modal fade" id="assignEmployeeModal" tabindex="-1" aria-labelledby="assignEmployeeModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="assignEmployeeModalLabel">Affecter un employé au projet</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('projects.employees.assign', $project) }}" method="POST">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label for="employee_id_assign" class="form-label">Employé <span class="text-danger">*</span></label>
            <select class="form-select @error('employee_id') is-invalid @enderror" id="employee_id_assign" name="employee_id" required>
              <option value="">Sélectionner un employé</option>
              @foreach(\App\Models\Employee::forCompany(auth()->user()->current_company_id)->active()->get() as $employee)
                @php
                  $existingPivot = $project->employees()->where('employees.id', $employee->id)->first();
                  $isActiveAssigned = $existingPivot && $existingPivot->pivot->is_active;
                @endphp
                @if(!$isActiveAssigned)
                  <option value="{{ $employee->id }}">
                    {{ $employee->full_name }} 
                    @if($employee->position) - {{ $employee->position }} @endif
                    @if($existingPivot && !$existingPivot->pivot->is_active) (Anciennement affecté) @endif
                  </option>
                @endif
              @endforeach
            </select>
            @error('employee_id')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label for="assigned_date" class="form-label">Date d'affectation</label>
            <input type="date" class="form-control @error('assigned_date') is-invalid @enderror" id="assigned_date" name="assigned_date" value="{{ old('assigned_date', now()->format('Y-m-d')) }}">
            @error('assigned_date')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label for="notes_assign" class="form-label">Notes</label>
            <textarea class="form-control @error('notes') is-invalid @enderror" id="notes_assign" name="notes" rows="3">{{ old('notes') }}</textarea>
            @error('notes')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary">Affecter</button>
        </div>
      </form>
    </div>
  </div>
</div>
@if(isset($expensesByType) && count($expensesByType) > 0)
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  // Graphique d'évolution de l'avancement
  @if(isset($progressChartData) && count($progressChartData) > 0)
  const progressEvolutionCtx = document.getElementById('progressEvolutionChart');
  if (progressEvolutionCtx) {
    const progressData = @json($progressChartData);
    const labels = progressData.map(item => {
      const date = new Date(item.date);
      return date.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' });
    });
    const progressValues = progressData.map(item => item.progress);

    new Chart(progressEvolutionCtx, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [{
          label: 'Avancement (%)',
          data: progressValues,
          borderColor: '#5D87FF',
          backgroundColor: 'rgba(93, 135, 255, 0.1)',
          tension: 0.4,
          fill: true,
          pointRadius: 5,
          pointHoverRadius: 7,
          pointBackgroundColor: '#5D87FF',
          pointBorderColor: '#fff',
          pointBorderWidth: 2
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
          legend: {
            display: true,
            position: 'top'
          },
          tooltip: {
            mode: 'index',
            intersect: false
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            max: 100,
            ticks: {
              callback: function(value) {
                return value + '%';
              }
            }
          },
          x: {
            ticks: {
              maxRotation: 45,
              minRotation: 45
            }
          }
        }
      }
    });
  }
  @endif

  // Graphique en camembert - Dépenses par type
  const expensesByTypeCtx = document.getElementById('expensesByTypeChart');
  if (expensesByTypeCtx) {
    new Chart(expensesByTypeCtx, {
      type: 'doughnut',
      data: {
        labels: {!! json_encode(array_map(function($type) {
          $types = [
            'materiaux' => 'Matériaux',
            'transport' => 'Transport',
            'main_oeuvre' => 'Main-d\'œuvre',
            'location' => 'Location',
            'autres' => 'Autres',
          ];
          return $types[$type] ?? ucfirst($type);
        }, array_keys($expensesByType))) !!},
        datasets: [{
          data: {!! json_encode(array_values($expensesByType)) !!},
          backgroundColor: [
            '#5D87FF',
            '#49BEFF',
            '#13DEB9',
            '#FFAE1F',
            '#FA896B'
          ]
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
      }
    });
  }

  // Graphique en ligne - Évolution des dépenses
  const expensesByMonthCtx = document.getElementById('expensesByMonthChart');
  if (expensesByMonthCtx) {
    new Chart(expensesByMonthCtx, {
      type: 'line',
      data: {
        labels: {!! json_encode(array_map(function($month) {
          return \Carbon\Carbon::createFromFormat('Y-m', $month)->format('M Y');
        }, array_keys($expensesByMonth))) !!},
        datasets: [{
          label: 'Dépenses (FCFA)',
          data: {!! json_encode(array_values($expensesByMonth)) !!},
          borderColor: '#5D87FF',
          backgroundColor: 'rgba(93, 135, 255, 0.1)',
          tension: 0.4,
          fill: true
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            beginAtZero: true
          }
        }
      }
    });
  }
</script>
@endpush
@endif
@endsection

