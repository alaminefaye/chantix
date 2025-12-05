@extends('layouts.app')

@section('title', 'Détails de la mise à jour')

@section('content')
<div class="row">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h5 class="card-title fw-semibold mb-0">Détails de la mise à jour</h5>
          <a href="{{ route('progress.index', $project) }}" class="btn btn-secondary">Retour</a>
        </div>

        <div class="mb-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
              <h6 class="fw-semibold mb-1">{{ $progressUpdate->user->name }}</h6>
              <small class="text-muted">{{ $progressUpdate->created_at->format('d/m/Y à H:i') }}</small>
            </div>
            <div>
              <span class="badge bg-primary rounded-3 fw-semibold fs-4">{{ $progressUpdate->progress_percentage }}%</span>
            </div>
          </div>

          <div class="mb-3">
            <h6 class="fw-semibold mb-2">Projet</h6>
            <p class="mb-0">{{ $project->name }}</p>
          </div>

          @if($progressUpdate->description)
            <div class="mb-3">
              <h6 class="fw-semibold mb-2">Description</h6>
              <p class="mb-0">{{ $progressUpdate->description }}</p>
            </div>
          @endif

          @if($progressUpdate->photos && count($progressUpdate->photos) > 0)
            <div class="mb-3">
              <h6 class="fw-semibold mb-2">Photos ({{ count($progressUpdate->photos) }})</h6>
              <div class="row g-2">
                @foreach($progressUpdate->photos as $photo)
                  <div class="col-md-4">
                    <a href="{{ asset('storage/' . $photo) }}" target="_blank">
                      <img src="{{ asset('storage/' . $photo) }}" alt="Photo" class="img-fluid rounded" style="max-height: 200px; object-fit: cover; width: 100%;">
                    </a>
                  </div>
                @endforeach
              </div>
            </div>
          @endif

          @if($progressUpdate->videos && count($progressUpdate->videos) > 0)
            <div class="mb-3">
              <h6 class="fw-semibold mb-2">Vidéos ({{ count($progressUpdate->videos) }})</h6>
              <div class="row g-2">
                @foreach($progressUpdate->videos as $video)
                  <div class="col-md-6">
                    <video controls class="w-100 rounded" style="max-height: 400px;">
                      <source src="{{ asset('storage/' . $video) }}" type="video/mp4">
                      Votre navigateur ne supporte pas la lecture de vidéos.
                    </video>
                  </div>
                @endforeach
              </div>
            </div>
          @endif

          @if($progressUpdate->latitude && $progressUpdate->longitude)
            <div class="mb-3">
              <h6 class="fw-semibold mb-2">Localisation GPS</h6>
              <p class="mb-0">
                <i class="ti ti-map-pin me-1"></i>
                Latitude: {{ $progressUpdate->latitude }}, Longitude: {{ $progressUpdate->longitude }}
              </p>
              <a href="https://www.google.com/maps?q={{ $progressUpdate->latitude }},{{ $progressUpdate->longitude }}" 
                 target="_blank" class="btn btn-sm btn-outline-primary mt-2">
                <i class="ti ti-map me-1"></i>Voir sur Google Maps
              </a>
            </div>
          @endif
        </div>

        @if(auth()->id() == $progressUpdate->user_id || auth()->user()->isSuperAdmin() || auth()->user()->hasRoleInCompany('admin', $project->company_id) || auth()->user()->hasPermission('progress.update', $project->company_id))
          <div class="d-flex gap-2">
            <form action="{{ route('progress.destroy', [$project, $progressUpdate]) }}" method="POST" 
                  onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette mise à jour ?');">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-danger">Supprimer</button>
            </form>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection

