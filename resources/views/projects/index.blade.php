@extends('layouts.app')

@section('title', 'Mes Projets')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h5 class="card-title fw-semibold mb-0">Mes Projets</h5>
          <div class="d-flex gap-2">
            <a href="{{ route('projects.index', array_merge(request()->all(), ['view' => 'list'])) }}" class="btn btn-sm btn-outline-primary {{ ($view ?? 'list') === 'list' ? 'active' : '' }}">
              <i class="ti ti-list me-1"></i>Liste
            </a>
            <a href="{{ route('projects.index', array_merge(request()->all(), ['view' => 'map'])) }}" class="btn btn-sm btn-outline-primary {{ ($view ?? 'list') === 'map' ? 'active' : '' }}">
              <i class="ti ti-map me-1"></i>Carte
            </a>
            @if(auth()->user()->hasPermission('projects.create') || auth()->user()->hasRoleInCompany('admin'))
              <a href="{{ route('projects.create') }}" class="btn btn-primary">
                <i class="ti ti-plus me-2"></i>Créer un projet
              </a>
            @endif
          </div>
        </div>

        @if(session('success'))
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        @endif

        <!-- Filtres -->
        <div class="card bg-light mb-4">
          <div class="card-body">
            <form method="GET" action="{{ route('projects.index') }}" class="row g-3">
              <div class="col-md-3">
                <label for="search" class="form-label">Recherche</label>
                <input type="text" class="form-control" id="search" name="search" value="{{ request('search') }}" placeholder="Nom, description...">
              </div>
              <div class="col-md-2">
                <label for="status" class="form-label">Statut</label>
                <select class="form-select" id="status" name="status">
                  <option value="">Tous</option>
                  <option value="non_demarre" {{ request('status') == 'non_demarre' ? 'selected' : '' }}>Non démarré</option>
                  <option value="en_cours" {{ request('status') == 'en_cours' ? 'selected' : '' }}>En cours</option>
                  <option value="termine" {{ request('status') == 'termine' ? 'selected' : '' }}>Terminé</option>
                  <option value="bloque" {{ request('status') == 'bloque' ? 'selected' : '' }}>Bloqué</option>
                </select>
              </div>
              <div class="col-md-2">
                <label for="created_by" class="form-label">Responsable</label>
                <select class="form-select" id="created_by" name="created_by">
                  <option value="">Tous</option>
                  @foreach($creators ?? [] as $creator)
                    <option value="{{ $creator->id }}" {{ request('created_by') == $creator->id ? 'selected' : '' }}>
                      {{ $creator->name }}
                    </option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-2">
                <label for="start_date_from" class="form-label">Date début (de)</label>
                <input type="date" class="form-control" id="start_date_from" name="start_date_from" value="{{ request('start_date_from') }}">
              </div>
              <div class="col-md-2">
                <label for="start_date_to" class="form-label">Date début (à)</label>
                <input type="date" class="form-control" id="start_date_to" name="start_date_to" value="{{ request('start_date_to') }}">
              </div>
              <div class="col-md-1 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                  <i class="ti ti-filter"></i>
                </button>
              </div>
              @if(request()->hasAny(['search', 'status', 'created_by', 'start_date_from', 'start_date_to']))
                <div class="col-12">
                  <a href="{{ route('projects.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="ti ti-x me-1"></i>Réinitialiser les filtres
                  </a>
                </div>
              @endif
            </form>
          </div>
        </div>

        @if(($view ?? 'list') === 'map')
          <!-- Vue Carte -->
          <div class="card mb-4">
            <div class="card-body">
              <div id="map" style="height: 600px; width: 100%;"></div>
            </div>
          </div>
          
          <!-- Liste des projets sur la carte -->
          <div class="row">
            @forelse($projects as $project)
              <div class="col-md-4 mb-3">
                <div class="card">
                  <div class="card-body">
                    <h6 class="fw-semibold mb-2">{{ $project->name }}</h6>
                    @if($project->address)
                      <p class="text-muted mb-2" style="font-size: 0.9rem;">
                        <i class="ti ti-map-pin me-1"></i>{{ Str::limit($project->address, 50) }}
                      </p>
                    @endif
                    <div class="d-flex justify-content-between align-items-center">
                      <span class="badge bg-{{ $statusColors[$project->status] ?? 'secondary' }}">
                        {{ $statusLabels[$project->status] ?? $project->status }}
                      </span>
                      <a href="{{ route('projects.show', $project) }}" class="btn btn-sm btn-primary">Voir</a>
                    </div>
                  </div>
                </div>
              </div>
            @empty
              <div class="col-12">
                <div class="alert alert-info">
                  <i class="ti ti-info-circle me-2"></i>
                  Aucun projet avec coordonnées GPS trouvé.
                  @if(auth()->user()->hasPermission('projects.create') || auth()->user()->hasRoleInCompany('admin'))
                    <a href="{{ route('projects.create') }}">Créer un projet</a>
                  @endif
                </div>
              </div>
            @endforelse
          </div>
        @else
          <!-- Vue Liste -->
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
                  <h6 class="fw-semibold mb-0">Dates</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Actions</h6>
                </th>
              </tr>
            </thead>
            <tbody>
              @forelse($projects as $project)
                <tr>
                  <td class="border-bottom-0">
                    <h6 class="fw-semibold mb-0">{{ $project->name }}</h6>
                    @if($project->description)
                      <p class="mb-0 fw-normal text-muted" style="font-size: 0.85rem;">{{ Str::limit($project->description, 50) }}</p>
                    @endif
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
                        <div class="progress-bar" role="progressbar" style="width: {{ $project->progress }}%" aria-valuenow="{{ $project->progress }}" aria-valuemin="0" aria-valuemax="100"></div>
                      </div>
                      <span class="ms-2">{{ $project->progress }}%</span>
                    </div>
                  </td>
                  <td class="border-bottom-0">
                    <h6 class="fw-semibold mb-0 fs-4">{{ number_format($project->budget, 2, ',', ' ') }} FCFA</h6>
                  </td>
                  <td class="border-bottom-0">
                    @if($project->start_date)
                      <p class="mb-0 fw-normal">Début: {{ $project->start_date->format('d/m/Y') }}</p>
                    @endif
                    @if($project->end_date)
                      <p class="mb-0 fw-normal">Fin: {{ $project->end_date->format('d/m/Y') }}</p>
                    @endif
                  </td>
                  <td class="border-bottom-0">
                    <div class="d-flex align-items-center gap-2">
                      <a href="{{ route('projects.show', $project) }}" class="btn btn-sm btn-info">Voir</a>
                      @if(auth()->user()->canManageProject($project, 'edit') || auth()->user()->hasRoleInCompany('admin'))
                        <a href="{{ route('projects.edit', $project) }}" class="btn btn-sm btn-warning">Modifier</a>
                      @endif
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="text-center py-4">
                    <p class="mb-0">Aucun projet trouvé.
                      @if(auth()->user()->hasPermission('projects.create') || auth()->user()->hasRoleInCompany('admin'))
                        <a href="{{ route('projects.create') }}">Créer un projet</a>
                      @endif
                    </p>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        @if(($view ?? 'list') === 'list' && method_exists($projects, 'hasPages') && $projects->hasPages())
          <div class="mt-4">
            {{ $projects->links() }}
          </div>
        @endif
        @endif
      </div>
    </div>
  </div>
</div>

@if(($view ?? 'list') === 'map')
@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser la carte
    const map = L.map('map').setView([46.2276, 2.2137], 6); // Centre de la France
    
    // Ajouter la couche de tuiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    
    // Ajouter les marqueurs pour chaque projet
    const projects = @json($projects);
    
    projects.forEach(function(project) {
        if (project.latitude && project.longitude) {
            const marker = L.marker([parseFloat(project.latitude), parseFloat(project.longitude)])
                .addTo(map);
            
            const statusColors = {
                'non_demarre': 'secondary',
                'en_cours': 'primary',
                'termine': 'success',
                'bloque': 'danger',
            };
            
            const statusLabels = {
                'non_demarre': 'Non démarré',
                'en_cours': 'En cours',
                'termine': 'Terminé',
                'bloque': 'Bloqué',
            };
            
            marker.bindPopup(`
                <div style="min-width: 200px;">
                    <h6 class="fw-semibold mb-2">${project.name}</h6>
                    <p class="mb-1"><strong>Statut:</strong> <span class="badge bg-${statusColors[project.status] || 'secondary'}">${statusLabels[project.status] || project.status}</span></p>
                    <p class="mb-1"><strong>Avancement:</strong> ${project.progress}%</p>
                    ${project.address ? `<p class="mb-1"><strong>Adresse:</strong> ${project.address}</p>` : ''}
                    <a href="/projects/${project.id}" class="btn btn-sm btn-primary mt-2">Voir le projet</a>
                </div>
            `);
        }
    });
    
    // Ajuster la vue pour afficher tous les marqueurs
    if (projects.length > 0) {
        const bounds = projects
            .filter(p => p.latitude && p.longitude)
            .map(p => [parseFloat(p.latitude), parseFloat(p.longitude)]);
        
        if (bounds.length > 0) {
            map.fitBounds(bounds, { padding: [50, 50] });
        }
    }
});
</script>
@endpush
@endif
@endsection

