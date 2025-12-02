@extends('layouts.app')

@section('title', 'Transférer ' . $material->name)

@section('content')
<div class="row">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h5 class="card-title fw-semibold mb-0">Transférer des matériaux</h5>
          <a href="{{ route('projects.show', $project) }}" class="btn btn-secondary">Retour</a>
        </div>

        @if(session('error'))
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        @endif

        <div class="alert alert-info mb-4">
          <h6 class="fw-semibold mb-2">Informations du transfert</h6>
          <p class="mb-1"><strong>Matériau:</strong> {{ $material->name }}</p>
          <p class="mb-1"><strong>Projet source:</strong> {{ $project->name }}</p>
          <p class="mb-1"><strong>Quantité disponible:</strong> 
            <span class="badge bg-success">{{ number_format($projectMaterial->quantity_remaining, 2, ',', ' ') }} {{ $material->unit }}</span>
          </p>
        </div>

        <form action="{{ route('projects.materials.transfer.store', ['project' => $project, 'material' => $material]) }}" method="POST">
          @csrf

          <div class="mb-3">
            <label for="destination_project_id" class="form-label">Projet destination <span class="text-danger">*</span></label>
            <select class="form-select @error('destination_project_id') is-invalid @enderror" id="destination_project_id" name="destination_project_id" required>
              <option value="">Sélectionner un projet</option>
              @foreach($otherProjects as $otherProject)
                <option value="{{ $otherProject->id }}" {{ old('destination_project_id') == $otherProject->id ? 'selected' : '' }}>
                  {{ $otherProject->name }}
                  @if($otherProject->address)
                    - {{ Str::limit($otherProject->address, 50) }}
                  @endif
                </option>
              @endforeach
            </select>
            @error('destination_project_id')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            @if($otherProjects->isEmpty())
              <small class="text-muted">Aucun autre projet disponible dans cette entreprise.</small>
            @endif
          </div>

          <div class="mb-3">
            <label for="quantity" class="form-label">Quantité à transférer <span class="text-danger">*</span></label>
            <div class="input-group">
              <input type="number" step="0.01" min="0.01" max="{{ $projectMaterial->quantity_remaining }}" 
                     class="form-control @error('quantity') is-invalid @enderror" 
                     id="quantity" name="quantity" 
                     value="{{ old('quantity') }}" 
                     required>
              <span class="input-group-text">{{ $material->unit }}</span>
            </div>
            <small class="text-muted">Maximum: {{ number_format($projectMaterial->quantity_remaining, 2, ',', ' ') }} {{ $material->unit }}</small>
            @error('quantity')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label for="notes" class="form-label">Notes (optionnel)</label>
            <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3" placeholder="Raison du transfert, commentaires...">{{ old('notes') }}</textarea>
            @error('notes')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="alert alert-warning">
            <i class="ti ti-alert-triangle me-2"></i>
            <strong>Attention:</strong> Cette action est irréversible. La quantité sera déduite du projet source et ajoutée au projet destination.
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary" {{ $otherProjects->isEmpty() ? 'disabled' : '' }}>
              <i class="ti ti-arrow-right me-2"></i>Effectuer le transfert
            </button>
            <a href="{{ route('projects.show', $project) }}" class="btn btn-secondary">Annuler</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

