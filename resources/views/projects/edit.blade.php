@extends('layouts.app')

@section('title', 'Modifier le projet')

@section('content')
<div class="row">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <h5 class="card-title fw-semibold mb-4">Modifier le projet: {{ $project->name }}</h5>

        @if ($errors->any())
          <div class="alert alert-danger">
            <ul class="mb-0">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <form action="{{ route('projects.update', $project) }}" method="POST">
          @csrf
          @method('PUT')
          <div class="mb-3">
            <label for="name" class="form-label">Nom du projet <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $project->name) }}" required>
          </div>
          <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control" id="description" name="description" rows="3">{{ old('description', $project->description) }}</textarea>
          </div>
          <div class="mb-3">
            <label for="address" class="form-label">Adresse du chantier</label>
            <input type="text" class="form-control" id="address" name="address" value="{{ old('address', $project->address) }}">
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="latitude" class="form-label">Latitude (GPS)</label>
              <input type="number" step="any" class="form-control" id="latitude" name="latitude" value="{{ old('latitude', $project->latitude) }}">
            </div>
            <div class="col-md-6 mb-3">
              <label for="longitude" class="form-label">Longitude (GPS)</label>
              <input type="number" step="any" class="form-control" id="longitude" name="longitude" value="{{ old('longitude', $project->longitude) }}">
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="start_date" class="form-label">Date de début</label>
              <input type="date" class="form-control" id="start_date" name="start_date" value="{{ old('start_date', $project->start_date?->format('Y-m-d')) }}">
            </div>
            <div class="col-md-6 mb-3">
              <label for="end_date" class="form-label">Date de fin prévue</label>
              <input type="date" class="form-control" id="end_date" name="end_date" value="{{ old('end_date', $project->end_date?->format('Y-m-d')) }}">
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="budget" class="form-label">Budget (€)</label>
              <input type="number" step="0.01" class="form-control" id="budget" name="budget" value="{{ old('budget', $project->budget) }}" min="0">
            </div>
            <div class="col-md-6 mb-3">
              <label for="status" class="form-label">Statut <span class="text-danger">*</span></label>
              <select class="form-select" id="status" name="status" required onchange="toggleStatusReason()">
                <option value="non_demarre" {{ old('status', $project->status) == 'non_demarre' ? 'selected' : '' }}>Non démarré</option>
                <option value="en_cours" {{ old('status', $project->status) == 'en_cours' ? 'selected' : '' }}>En cours</option>
                <option value="termine" {{ old('status', $project->status) == 'termine' ? 'selected' : '' }}>Terminé</option>
                <option value="bloque" {{ old('status', $project->status) == 'bloque' ? 'selected' : '' }}>Bloqué</option>
              </select>
            </div>
          </div>
          <div class="mb-3" id="statusReasonField" style="display: none;">
            <label for="status_change_reason" class="form-label">Raison du changement de statut</label>
            <textarea class="form-control" id="status_change_reason" name="status_change_reason" rows="2" placeholder="Expliquez la raison du changement de statut...">{{ old('status_change_reason') }}</textarea>
            <small class="text-muted">Ce champ apparaît uniquement si vous changez le statut</small>
          </div>
          <div class="mb-3">
            <label for="progress" class="form-label">Avancement (%)</label>
            <input type="number" class="form-control" id="progress" name="progress" value="{{ old('progress', $project->progress) }}" min="0" max="100">
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="client_name" class="form-label">Nom du client</label>
              <input type="text" class="form-control" id="client_name" name="client_name" value="{{ old('client_name', $project->client_name) }}">
            </div>
            <div class="col-md-6 mb-3">
              <label for="client_contact" class="form-label">Contact client</label>
              <input type="text" class="form-control" id="client_contact" name="client_contact" value="{{ old('client_contact', $project->client_contact) }}">
            </div>
          </div>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Mettre à jour</button>
            <a href="{{ route('projects.show', $project) }}" class="btn btn-secondary">Annuler</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
function toggleStatusReason() {
    const statusSelect = document.getElementById('status');
    const statusReasonField = document.getElementById('statusReasonField');
    const currentStatus = '{{ $project->status }}';
    
    if (statusSelect.value !== currentStatus) {
        statusReasonField.style.display = 'block';
    } else {
        statusReasonField.style.display = 'none';
    }
}

// Vérifier au chargement
document.addEventListener('DOMContentLoaded', toggleStatusReason);
</script>
@endsection

