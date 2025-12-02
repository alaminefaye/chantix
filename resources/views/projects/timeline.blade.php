@extends('layouts.app')

@section('title', 'Timeline - ' . $project->name)

@section('content')
<div class="row">
  <div class="col-lg-10">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h5 class="card-title fw-semibold mb-0">
            <i class="ti ti-timeline me-2"></i>Timeline - {{ $project->name }}
          </h5>
          <a href="{{ route('projects.show', $project) }}" class="btn btn-secondary">Retour</a>
        </div>

        @if($events->count() > 0)
          <div class="timeline">
            @foreach($events as $event)
              <div class="timeline-item mb-4">
                <div class="d-flex">
                  <div class="timeline-marker me-3">
                    @php
                      $icons = [
                        'progress' => 'ti-progress',
                        'task' => 'ti-checklist',
                        'expense' => 'ti-currency-euro',
                        'comment' => 'ti-message-circle',
                      ];
                      $colors = [
                        'progress' => 'primary',
                        'task' => 'success',
                        'expense' => 'info',
                        'comment' => 'dark',
                      ];
                    @endphp
                    <div class="avatar-sm bg-{{ $colors[$event['type']] ?? 'secondary' }} text-white rounded-circle d-flex align-items-center justify-content-center">
                      <i class="ti {{ $icons[$event['type']] ?? 'ti-circle' }}"></i>
                    </div>
                  </div>
                  <div class="flex-grow-1">
                    <div class="card">
                      <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                          <div>
                            <h6 class="fw-semibold mb-1">{{ $event['title'] }}</h6>
                            <small class="text-muted">
                              <i class="ti ti-user me-1"></i>{{ $event['user'] }}
                              <span class="ms-2">
                                <i class="ti ti-clock me-1"></i>{{ $event['date']->format('d/m/Y à H:i') }}
                              </span>
                            </small>
                          </div>
                          <span class="badge bg-{{ $colors[$event['type']] ?? 'secondary' }}">{{ ucfirst($event['type']) }}</span>
                        </div>
                        @if($event['description'])
                          <p class="mb-0">{{ Str::limit($event['description'], 200) }}</p>
                        @endif
                        @if($event['type'] === 'progress' && isset($event['data']->progress_percentage))
                          <div class="mt-2">
                            <small class="text-muted">Avancement: {{ $event['data']->progress_percentage }}%</small>
                            <div class="progress mt-1" style="height: 6px;">
                              <div class="progress-bar" style="width: {{ $event['data']->progress_percentage }}%"></div>
                            </div>
                          </div>
                        @endif
                        @if($event['type'] === 'task')
                          <div class="mt-2">
                            <span class="badge bg-{{ $event['data']->status === 'termine' ? 'success' : ($event['data']->status === 'en_cours' ? 'primary' : 'secondary') }}">
                              {{ ucfirst($event['data']->status) }}
                            </span>
                            @if($event['data']->priority)
                              <span class="badge bg-warning ms-1">{{ ucfirst($event['data']->priority) }}</span>
                            @endif
                          </div>
                        @endif
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            @endforeach
          </div>
        @else
          <div class="text-center py-5">
            <i class="ti ti-timeline" style="font-size: 48px; color: #ccc;"></i>
            <p class="text-muted mt-3">Aucun événement pour le moment.</p>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>

<style>
.timeline {
  position: relative;
  padding-left: 20px;
}

.timeline::before {
  content: '';
  position: absolute;
  left: 15px;
  top: 0;
  bottom: 0;
  width: 2px;
  background: #e0e0e0;
}

.timeline-item {
  position: relative;
}

.timeline-marker {
  position: relative;
  z-index: 1;
}

.avatar-sm {
  width: 40px;
  height: 40px;
  font-size: 18px;
}
</style>
@endsection

