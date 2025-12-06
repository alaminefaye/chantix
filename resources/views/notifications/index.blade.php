@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<div class="container-fluid">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="card-title fw-semibold mb-0">
              <i class="ti ti-bell me-2"></i>Mes Notifications
            </h5>
            @if($notifications->where('is_read', false)->count() > 0)
              <form action="{{ route('notifications.read-all') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-primary">
                  <i class="ti ti-check me-1"></i>Tout marquer comme lu
                </button>
              </form>
            @endif
          </div>

          @if($notifications->count() > 0)
            <div class="list-group">
              @foreach($notifications as $notification)
                <a href="{{ $notification->link ?: '#' }}" class="list-group-item list-group-item-action {{ !$notification->is_read ? 'bg-light' : '' }}" 
                   onclick="event.preventDefault(); markAsRead({{ $notification->id }}, '{{ $notification->link ?: '#' }}');">
                  <div class="d-flex align-items-start">
                    <div class="flex-shrink-0 me-3">
                      @php
                        $icons = [
                          'comment' => 'ti-message-circle',
                          'mention' => 'ti-at',
                          'task_assigned' => 'ti-checklist',
                          'progress_update' => 'ti-progress',
                          'expense_added' => 'ti-currency-euro',
                          'expense_created' => 'ti-currency-euro',
                          'expense_updated' => 'ti-currency-euro',
                        ];
                        $icon = $icons[$notification->type] ?? 'ti-bell';
                        $colors = [
                          'comment' => 'text-primary',
                          'mention' => 'text-warning',
                          'task_assigned' => 'text-info',
                          'progress_update' => 'text-success',
                          'expense_added' => 'text-danger',
                          'expense_created' => 'text-danger',
                          'expense_updated' => 'text-warning',
                        ];
                        $color = $colors[$notification->type] ?? 'text-secondary';
                      @endphp
                      <div class="rounded-circle bg-light p-2">
                        <i class="ti {{ $icon }} fs-5 {{ $color }}"></i>
                      </div>
                    </div>
                    <div class="flex-grow-1">
                      <div class="d-flex justify-content-between align-items-start mb-1">
                        <h6 class="mb-0 fw-semibold">{{ $notification->title }}</h6>
                        @if(!$notification->is_read)
                          <span class="badge bg-primary rounded-circle" style="width: 8px; height: 8px;"></span>
                        @endif
                      </div>
                      <p class="mb-1 text-muted">{{ $notification->message }}</p>
                      @if($notification->project)
                        <small class="text-muted">
                          <i class="ti ti-building me-1"></i>{{ $notification->project->name }}
                        </small>
                      @endif
                      <div class="mt-1">
                        <small class="text-muted">
                          <i class="ti ti-clock me-1"></i>{{ $notification->created_at->diffForHumans() }}
                        </small>
                      </div>
                    </div>
                  </div>
                </a>
              @endforeach
            </div>

            <div class="mt-4">
              {{ $notifications->links() }}
            </div>
          @else
            <div class="text-center py-5">
              <i class="ti ti-bell-off fs-1 text-muted mb-3"></i>
              <p class="text-muted">Aucune notification</p>
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function markAsRead(notificationId, link) {
  fetch(`/notifications/${notificationId}/read`, {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': '{{ csrf_token() }}',
      'Content-Type': 'application/json',
    },
  }).then(() => {
    if (link && link !== '#') {
      window.location.href = link;
    } else {
      location.reload();
    }
  });
}
</script>
@endsection
