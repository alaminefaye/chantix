@extends('layouts.app')

@section('title', 'Nouvelle mise à jour - ' . $project->name)

@section('content')
<div class="row">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h5 class="card-title fw-semibold mb-0">Nouvelle mise à jour d'avancement</h5>
          <a href="{{ route('progress.index', $project) }}" class="btn btn-secondary">Retour</a>
        </div>

        <p class="text-muted mb-4">Projet: <strong>{{ $project->name }}</strong></p>

        @if ($errors->any())
          <div class="alert alert-danger">
            <ul class="mb-0">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <form action="{{ route('progress.store', $project) }}" method="POST" enctype="multipart/form-data">
          @csrf
          
          <div class="mb-3">
            <label for="progress_percentage" class="form-label">Pourcentage d'avancement <span class="text-danger">*</span></label>
            <input type="number" class="form-control" id="progress_percentage" name="progress_percentage" 
                   value="{{ old('progress_percentage', $project->progress) }}" min="0" max="100" required>
            <small class="text-muted">Avancement actuel du projet: {{ $project->progress }}%</small>
          </div>

          <div class="mb-3">
            <label for="description" class="form-label">Description / Rapport</label>
            <textarea class="form-control" id="description" name="description" rows="5" 
                      placeholder="Décrivez l'avancement des travaux, les tâches réalisées, les problèmes rencontrés...">{{ old('description') }}</textarea>
          </div>

          <div class="mb-3">
            <label for="photos" class="form-label">Photos</label>
            <input type="file" class="form-control" id="photos" name="photos[]" multiple accept="image/*">
            <small class="text-muted">Vous pouvez sélectionner plusieurs photos (max 5MB par photo)</small>
          </div>

          <div class="mb-3">
            <label for="videos" class="form-label">Vidéos</label>
            <input type="file" class="form-control" id="videos" name="videos[]" multiple accept="video/*">
            <small class="text-muted">Vous pouvez sélectionner plusieurs vidéos (max 50MB par vidéo)</small>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="latitude" class="form-label">Latitude (GPS)</label>
              <input type="number" step="any" class="form-control" id="latitude" name="latitude" 
                     value="{{ old('latitude') }}" placeholder="Ex: 48.8566">
            </div>
            <div class="col-md-6 mb-3">
              <label for="longitude" class="form-label">Longitude (GPS)</label>
              <input type="number" step="any" class="form-control" id="longitude" name="longitude" 
                     value="{{ old('longitude') }}" placeholder="Ex: 2.3522">
            </div>
          </div>

          <div class="alert alert-info">
            <i class="ti ti-info-circle me-2"></i>
            <strong>Note:</strong> Le pourcentage d'avancement du projet sera automatiquement mis à jour. 
            Si vous atteignez 100%, le statut du projet passera à "Terminé".
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Enregistrer la mise à jour</button>
            <a href="{{ route('progress.index', $project) }}" class="btn btn-secondary">Annuler</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
  // Géolocalisation automatique si disponible
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(function(position) {
      document.getElementById('latitude').value = position.coords.latitude.toFixed(8);
      document.getElementById('longitude').value = position.coords.longitude.toFixed(8);
    });
  }
</script>
@endpush
@endsection

