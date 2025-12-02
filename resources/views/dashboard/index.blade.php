@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
@if(!auth()->user()->current_company_id)
  <div class="alert alert-warning">
    <i class="ti ti-alert-circle me-2"></i>
    Veuillez <a href="{{ route('companies.index') }}">sélectionner une entreprise</a> pour voir le dashboard.
  </div>
@else

@if(isset($searchQuery) && $searchQuery)
  <!-- Résultats de recherche -->
  <div class="card mb-4">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="card-title fw-semibold mb-0">
          <i class="ti ti-search me-2"></i>Résultats de recherche pour "{{ $searchQuery }}"
        </h5>
        <a href="{{ route('dashboard') }}" class="btn btn-sm btn-secondary">
          <i class="ti ti-x me-1"></i>Effacer
        </a>
      </div>

      <div class="row">
        @if(isset($searchResults['projects']) && $searchResults['projects']->count() > 0)
          <div class="col-md-6 mb-3">
            <h6 class="fw-semibold mb-2">
              <i class="ti ti-building me-2"></i>Projets ({{ $searchResults['projects']->count() }})
            </h6>
            <div class="list-group">
              @foreach($searchResults['projects'] as $project)
                <a href="{{ route('projects.show', $project) }}" class="list-group-item list-group-item-action">
                  <div class="d-flex justify-content-between align-items-start">
                    <div>
                      <h6 class="mb-1">{{ $project->name }}</h6>
                      <p class="mb-0 text-muted" style="font-size: 0.85rem;">{{ Str::limit($project->description, 60) }}</p>
                    </div>
                    <span class="badge bg-primary">{{ $project->status }}</span>
                  </div>
                </a>
              @endforeach
            </div>
          </div>
        @endif

        @if(isset($searchResults['materials']) && $searchResults['materials']->count() > 0)
          <div class="col-md-6 mb-3">
            <h6 class="fw-semibold mb-2">
              <i class="ti ti-package me-2"></i>Matériaux ({{ $searchResults['materials']->count() }})
            </h6>
            <div class="list-group">
              @foreach($searchResults['materials'] as $material)
                <a href="{{ route('materials.show', $material) }}" class="list-group-item list-group-item-action">
                  <div class="d-flex justify-content-between align-items-start">
                    <div>
                      <h6 class="mb-1">{{ $material->name }}</h6>
                      <p class="mb-0 text-muted" style="font-size: 0.85rem;">
                        Stock: {{ $material->current_stock }} {{ $material->unit }}
                      </p>
                    </div>
                    @if($material->isLowStock())
                      <span class="badge bg-warning">Stock faible</span>
                    @endif
                  </div>
                </a>
              @endforeach
            </div>
          </div>
        @endif

        @if(isset($searchResults['employees']) && $searchResults['employees']->count() > 0)
          <div class="col-md-6 mb-3">
            <h6 class="fw-semibold mb-2">
              <i class="ti ti-users me-2"></i>Employés ({{ $searchResults['employees']->count() }})
            </h6>
            <div class="list-group">
              @foreach($searchResults['employees'] as $employee)
                <a href="{{ route('employees.show', $employee) }}" class="list-group-item list-group-item-action">
                  <div class="d-flex justify-content-between align-items-start">
                    <div>
                      <h6 class="mb-1">{{ $employee->full_name }}</h6>
                      <p class="mb-0 text-muted" style="font-size: 0.85rem;">
                        @if($employee->position){{ $employee->position }}@endif
                        @if($employee->email) - {{ $employee->email }}@endif
                      </p>
                    </div>
                    @if($employee->is_active)
                      <span class="badge bg-success">Actif</span>
                    @endif
                  </div>
                </a>
              @endforeach
            </div>
          </div>
        @endif

        @if(isset($searchResults['tasks']) && $searchResults['tasks']->count() > 0)
          <div class="col-md-6 mb-3">
            <h6 class="fw-semibold mb-2">
              <i class="ti ti-checklist me-2"></i>Tâches ({{ $searchResults['tasks']->count() }})
            </h6>
            <div class="list-group">
              @foreach($searchResults['tasks'] as $task)
                <a href="{{ route('projects.show', $task->project) }}#tasks" class="list-group-item list-group-item-action">
                  <div class="d-flex justify-content-between align-items-start">
                    <div>
                      <h6 class="mb-1">{{ $task->title }}</h6>
                      <p class="mb-0 text-muted" style="font-size: 0.85rem;">
                        Projet: {{ $task->project->name }}
                      </p>
                    </div>
                    <span class="badge bg-{{ $task->status === 'termine' ? 'success' : ($task->status === 'en_cours' ? 'primary' : 'secondary') }}">
                      {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                    </span>
                  </div>
                </a>
              @endforeach
            </div>
          </div>
        @endif
      </div>

      @if((!isset($searchResults['projects']) || $searchResults['projects']->count() == 0) &&
          (!isset($searchResults['materials']) || $searchResults['materials']->count() == 0) &&
          (!isset($searchResults['employees']) || $searchResults['employees']->count() == 0) &&
          (!isset($searchResults['tasks']) || $searchResults['tasks']->count() == 0))
        <div class="alert alert-info">
          <i class="ti ti-info-circle me-2"></i>
          Aucun résultat trouvé pour "{{ $searchQuery }}"
        </div>
      @endif
    </div>
  </div>
@endif

<!-- Statistiques principales -->
<div class="row">
  <div class="col-sm-6 col-xl-3">
    <div class="card overflow-hidden rounded-2">
      <div class="card-body p-4">
        <div class="d-flex align-items-center">
          <div class="flex-shrink-0">
            <div class="text-white bg-primary rounded-circle p-3 d-flex align-items-center justify-content-center">
              <i class="ti ti-building fs-4"></i>
            </div>
          </div>
          <div class="ms-3">
            <h6 class="fw-semibold mb-0">Total Projets</h6>
            <h4 class="fw-semibold mb-0">{{ $totalProjects }}</h4>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card overflow-hidden rounded-2">
      <div class="card-body p-4">
        <div class="d-flex align-items-center">
          <div class="flex-shrink-0">
            <div class="text-white bg-success rounded-circle p-3 d-flex align-items-center justify-content-center">
              <i class="ti ti-check fs-4"></i>
            </div>
          </div>
          <div class="ms-3">
            <h6 class="fw-semibold mb-0">Projets Actifs</h6>
            <h4 class="fw-semibold mb-0">{{ $activeProjects }}</h4>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card overflow-hidden rounded-2">
      <div class="card-body p-4">
        <div class="d-flex align-items-center">
          <div class="flex-shrink-0">
            <div class="text-white bg-info rounded-circle p-3 d-flex align-items-center justify-content-center">
              <i class="ti ti-currency-euro fs-4"></i>
            </div>
          </div>
          <div class="ms-3">
            <h6 class="fw-semibold mb-0">Budget Total</h6>
            <h4 class="fw-semibold mb-0">{{ number_format($totalBudget, 0, ',', ' ') }} €</h4>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card overflow-hidden rounded-2">
      <div class="card-body p-4">
        <div class="d-flex align-items-center">
          <div class="flex-shrink-0">
            <div class="text-white bg-warning rounded-circle p-3 d-flex align-items-center justify-content-center">
              <i class="ti ti-progress fs-4"></i>
            </div>
          </div>
          <div class="ms-3">
            <h6 class="fw-semibold mb-0">Avancement Moyen</h6>
            <h4 class="fw-semibold mb-0">{{ number_format($averageProgress, 1) }}%</h4>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Graphiques et détails -->
<div class="row">
  <div class="col-lg-8 d-flex align-items-strech">
    <div class="card w-100">
      <div class="card-body">
        <div class="d-sm-flex d-block align-items-center justify-content-between mb-9">
          <div class="mb-3 mb-sm-0">
            <h5 class="card-title fw-semibold">Répartition des Projets</h5>
          </div>
        </div>
        <div id="chart"></div>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="row">
      <div class="col-lg-12">
        <div class="card overflow-hidden">
          <div class="card-body p-4">
            <h5 class="card-title mb-9 fw-semibold">Statuts des Projets</h5>
            <div class="row align-items-center">
              <div class="col-12">
                <div class="mb-3">
                  <div class="d-flex justify-content-between mb-2">
                    <span>En cours</span>
                    <span class="fw-semibold">{{ $projectsByStatus['en_cours'] }}</span>
                  </div>
                  <div class="progress" style="height: 8px;">
                    <div class="progress-bar bg-primary" style="width: {{ $totalProjects > 0 ? ($projectsByStatus['en_cours'] / $totalProjects * 100) : 0 }}%"></div>
                  </div>
                </div>
                <div class="mb-3">
                  <div class="d-flex justify-content-between mb-2">
                    <span>Terminés</span>
                    <span class="fw-semibold">{{ $projectsByStatus['termine'] }}</span>
                  </div>
                  <div class="progress" style="height: 8px;">
                    <div class="progress-bar bg-success" style="width: {{ $totalProjects > 0 ? ($projectsByStatus['termine'] / $totalProjects * 100) : 0 }}%"></div>
                  </div>
                </div>
                <div class="mb-3">
                  <div class="d-flex justify-content-between mb-2">
                    <span>Non démarrés</span>
                    <span class="fw-semibold">{{ $projectsByStatus['non_demarre'] }}</span>
                  </div>
                  <div class="progress" style="height: 8px;">
                    <div class="progress-bar bg-secondary" style="width: {{ $totalProjects > 0 ? ($projectsByStatus['non_demarre'] / $totalProjects * 100) : 0 }}%"></div>
                  </div>
                </div>
                <div class="mb-0">
                  <div class="d-flex justify-content-between mb-2">
                    <span>Bloqués</span>
                    <span class="fw-semibold">{{ $projectsByStatus['bloque'] }}</span>
                  </div>
                  <div class="progress" style="height: 8px;">
                    <div class="progress-bar bg-danger" style="width: {{ $totalProjects > 0 ? ($projectsByStatus['bloque'] / $totalProjects * 100) : 0 }}%"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Projets récents -->
<div class="row">
  <div class="col-lg-12">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h5 class="card-title fw-semibold mb-0">Projets Récents</h5>
          <a href="{{ route('projects.index') }}" class="btn btn-sm btn-primary">Voir tous</a>
        </div>
        <div class="table-responsive">
          <table class="table text-nowrap mb-0 align-middle">
            <thead class="text-dark fs-4">
              <tr>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Nom</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Statut</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Avancement</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Budget</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Actions</h6>
                </th>
              </tr>
            </thead>
            <tbody>
              @forelse($recentProjects as $project)
                <tr>
                  <td class="border-bottom-0">
                    <h6 class="fw-semibold mb-0">{{ $project->name }}</h6>
                  </td>
                  <td class="border-bottom-0">
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
                    <span class="badge bg-{{ $statusColors[$project->status] ?? 'secondary' }} rounded-3 fw-semibold">
                      {{ $statusLabels[$project->status] ?? $project->status }}
                    </span>
                  </td>
                  <td class="border-bottom-0">
                    <div class="d-flex align-items-center">
                      <div class="progress" style="width: 100px; height: 8px;">
                        <div class="progress-bar" style="width: {{ $project->progress }}%"></div>
                      </div>
                      <span class="ms-2">{{ $project->progress }}%</span>
                    </div>
                  </td>
                  <td class="border-bottom-0">
                    <h6 class="fw-semibold mb-0 fs-4">{{ number_format($project->budget, 0, ',', ' ') }} €</h6>
                  </td>
                  <td class="border-bottom-0">
                    <a href="{{ route('projects.show', $project) }}" class="btn btn-sm btn-info">Voir</a>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="text-center py-4">
                    <p class="mb-0">Aucun projet trouvé. <a href="{{ route('projects.create') }}">Créer un projet</a></p>
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
@endif
@endsection

@push('scripts')
<script src="{{ asset('assets/libs/apexcharts/dist/apexcharts.min.js') }}"></script>
<script>
  $(function () {
    // Graphique de répartition des projets
    var chart = {
      series: [
        { name: "Projets", data: [
          {{ $projectsByStatus['non_demarre'] }},
          {{ $projectsByStatus['en_cours'] }},
          {{ $projectsByStatus['termine'] }},
          {{ $projectsByStatus['bloque'] }}
        ]}
      ],
      chart: {
        type: "bar",
        height: 345,
        toolbar: { show: false },
        foreColor: "#adb0bb",
        fontFamily: 'inherit',
      },
      colors: ["#5D87FF", "#49BEFF", "#13DEB9", "#FFAE1F"],
      plotOptions: {
        bar: {
          horizontal: false,
          columnWidth: "35%",
          borderRadius: [6],
        },
      },
      dataLabels: {
        enabled: false,
      },
      legend: {
        show: false,
      },
      grid: {
        borderColor: "rgba(0,0,0,0.1)",
        strokeDashArray: 3,
      },
      xaxis: {
        type: "category",
        categories: ["Non démarré", "En cours", "Terminé", "Bloqué"],
        labels: {
          style: { cssClass: "grey--text lighten-2--text fill-color" },
        },
      },
      yaxis: {
        show: true,
        min: 0,
        labels: {
          style: {
            cssClass: "grey--text lighten-2--text fill-color",
          },
        },
      },
      tooltip: { theme: "light" },
    };

    var chart = new ApexCharts(document.querySelector("#chart"), chart);
    chart.render();
  });
</script>
@endpush
