@extends('layouts.app')

@section('title', 'Galerie - ' . $project->name)

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h5 class="card-title fw-semibold mb-0">
            <i class="ti ti-photo me-2"></i>Galerie - {{ $project->name }}
          </h5>
          <a href="{{ route('projects.show', $project) }}" class="btn btn-secondary">Retour</a>
        </div>

        <ul class="nav nav-tabs mb-4" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#photos" type="button" role="tab">
              Photos ({{ count($allPhotos) }})
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#videos" type="button" role="tab">
              Vidéos ({{ count($allVideos) }})
            </button>
          </li>
        </ul>

        <div class="tab-content">
          <!-- Onglet Photos -->
          <div class="tab-pane fade show active" id="photos" role="tabpanel">
            @if(count($allPhotos) > 0)
              <div class="row g-3">
                @foreach($allPhotos as $item)
                  <div class="col-md-3 col-sm-4 col-6">
                    <div class="card">
                      <a href="{{ Storage::url($item['path']) }}" data-lightbox="gallery" data-title="{{ $item['update']->description ?? 'Photo du ' . $item['date']->format('d/m/Y') }}">
                        <img src="{{ Storage::url($item['path']) }}" alt="Photo" class="card-img-top" style="height: 200px; object-fit: cover; cursor: pointer;">
                      </a>
                      <div class="card-body p-2">
                        <small class="text-muted">
                          <i class="ti ti-calendar"></i> {{ $item['date']->format('d/m/Y') }}
                        </small>
                        @if($item['update']->user)
                          <br><small class="text-muted">
                            <i class="ti ti-user"></i> {{ $item['update']->user->name }}
                          </small>
                        @endif
                      </div>
                    </div>
                  </div>
                @endforeach
              </div>
            @else
              <div class="text-center py-5">
                <i class="ti ti-photo-off" style="font-size: 48px; color: #ccc;"></i>
                <p class="text-muted mt-3">Aucune photo pour le moment.</p>
              </div>
            @endif
          </div>

          <!-- Onglet Vidéos -->
          <div class="tab-pane fade" id="videos" role="tabpanel">
            @if(count($allVideos) > 0)
              <div class="row g-3">
                @foreach($allVideos as $item)
                  <div class="col-md-6">
                    <div class="card">
                      <video controls class="w-100" style="max-height: 400px;">
                        <source src="{{ Storage::url($item['path']) }}" type="video/mp4">
                        Votre navigateur ne supporte pas la lecture de vidéos.
                      </video>
                      <div class="card-body">
                        <small class="text-muted">
                          <i class="ti ti-calendar"></i> {{ $item['date']->format('d/m/Y') }}
                        </small>
                        @if($item['update']->user)
                          <br><small class="text-muted">
                            <i class="ti ti-user"></i> {{ $item['update']->user->name }}
                          </small>
                        @endif
                      </div>
                    </div>
                  </div>
                @endforeach
              </div>
            @else
              <div class="text-center py-5">
                <i class="ti ti-video-off" style="font-size: 48px; color: #ccc;"></i>
                <p class="text-muted mt-3">Aucune vidéo pour le moment.</p>
              </div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>
<script>
  lightbox.option({
    'resizeDuration': 200,
    'wrapAround': true,
    'albumLabel': 'Image %1 sur %2'
  });
</script>
@endpush
@endsection

