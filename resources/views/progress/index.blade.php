@extends('layouts.app')

@section('title', 'Mises à jour - ' . $project->name)

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <div>
            <h5 class="card-title fw-semibold mb-0">Mises à jour d'avancement</h5>
            <p class="text-muted mb-0">Projet: <strong>{{ $project->name }}</strong></p>
          </div>
          <div class="d-flex gap-2">
            @if(auth()->user()->isSuperAdmin() || auth()->user()->hasRoleInCompany('admin', $project->company_id) || auth()->user()->hasPermission('progress.update', $project->company_id))
              <a href="{{ route('progress.create', $project) }}" class="btn btn-primary">
                <i class="ti ti-plus me-2"></i>Nouvelle mise à jour
              </a>
            @endif
            <a href="{{ route('projects.show', $project) }}" class="btn btn-secondary">Retour au projet</a>
          </div>
        </div>

        @if(session('success'))
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        @endif

        @forelse($updates as $update)
          <div class="card mb-3">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                  <h6 class="fw-semibold mb-1">{{ $update->user->name }}</h6>
                  <small class="text-muted">{{ $update->created_at->format('d/m/Y à H:i') }}</small>
                </div>
                <div>
                  <span class="badge bg-primary rounded-3 fw-semibold fs-4">{{ $update->progress_percentage }}%</span>
                </div>
              </div>

              @if($update->description)
                <p class="mb-3">{{ $update->description }}</p>
              @endif

              @if($update->photos && count($update->photos) > 0)
                <div class="mb-3">
                  <h6 class="fw-semibold mb-2">Photos</h6>
                  <div class="row g-2">
                    @foreach($update->photos as $photo)
                      <div class="col-md-3">
                        <a href="{{ asset('storage/' . $photo) }}" target="_blank">
                          <img src="{{ asset('storage/' . $photo) }}" alt="Photo" class="img-fluid rounded" style="max-height: 150px; object-fit: cover; width: 100%;">
                        </a>
                      </div>
                    @endforeach
                  </div>
                </div>
              @endif

              @if($update->videos && count($update->videos) > 0)
                <div class="mb-3">
                  <h6 class="fw-semibold mb-2">Vidéos</h6>
                  <div class="row g-2">
                    @foreach($update->videos as $video)
                      <div class="col-md-6">
                        <video controls class="w-100 rounded" style="max-height: 300px;">
                          <source src="{{ asset('storage/' . $video) }}" type="video/mp4">
                        </video>
                      </div>
                    @endforeach
                  </div>
                </div>
              @endif

              @if($update->audio_file)
                <div class="mb-3">
                  <h6 class="fw-semibold mb-2">Rapport audio</h6>
                  <div class="card border-0 bg-light">
                    <div class="card-body">
                      <div class="d-flex align-items-center gap-3">
                        <div class="bg-success bg-opacity-10 rounded-circle p-3">
                          <i class="ti ti-microphone text-success fs-4"></i>
                        </div>
                        <div class="flex-grow-1">
                          <p class="mb-1 fw-semibold">Audio disponible</p>
                          <audio controls class="w-100" style="max-width: 100%;">
                            <source src="{{ asset('storage/' . $update->audio_file) }}" type="audio/mpeg">
                            <source src="{{ asset('storage/' . $update->audio_file) }}" type="audio/mp4">
                            <source src="{{ asset('storage/' . $update->audio_file) }}" type="audio/wav">
                            Votre navigateur ne supporte pas la lecture audio.
                          </audio>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              @endif

              @if($update->latitude && $update->longitude)
                <div class="mb-2">
                  <small class="text-muted">
                    <i class="ti ti-map-pin me-1"></i>
                    Localisation: {{ $update->latitude }}, {{ $update->longitude }}
                  </small>
                </div>
              @endif

              <div class="d-flex gap-2">
                <a href="{{ route('progress.show', [$project, $update]) }}" class="btn btn-sm btn-info">Voir détails</a>
                @if(auth()->id() == $update->user_id || auth()->user()->isSuperAdmin() || auth()->user()->hasRoleInCompany('admin', $project->company_id) || auth()->user()->hasPermission('progress.update', $project->company_id))
                  <form action="{{ route('progress.destroy', [$project, $update]) }}" method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette mise à jour ?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger">Supprimer</button>
                  </form>
                @endif
              </div>
            </div>
          </div>
        @empty
          <div class="text-center py-5">
            <i class="ti ti-inbox fs-1 text-muted mb-3"></i>
            <p class="text-muted">Aucune mise à jour d'avancement pour ce projet.</p>
            @if(auth()->user()->isSuperAdmin() || auth()->user()->hasRoleInCompany('admin', $project->company_id) || auth()->user()->hasPermission('progress.update', $project->company_id))
              <a href="{{ route('progress.create', $project) }}" class="btn btn-primary">Créer la première mise à jour</a>
            @endif
          </div>
        @endforelse

        @if($updates->hasPages())
          <div class="mt-4">
            {{ $updates->links() }}
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection

