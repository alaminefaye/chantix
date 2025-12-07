@extends('layouts.app')

@section('title', 'Détails de l\'invitation - ' . $company->name)

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h5 class="card-title fw-semibold mb-0">Détails de l'invitation</h5>
          <div class="d-flex gap-2">
            <a href="{{ route('invitations.index', $company) }}" class="btn btn-secondary">
              <i class="ti ti-arrow-left me-2"></i>Retour
            </a>
            @if($invitation->status === 'pending' && !$invitation->isExpired())
              <a href="{{ route('invitations.edit', ['company' => $company, 'invitation' => $invitation]) }}" class="btn btn-warning">
                <i class="ti ti-edit me-2"></i>Modifier
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

        @if(session('error'))
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        @endif

        <div class="row">
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label fw-semibold">Email</label>
              <p class="mb-0">{{ $invitation->email }}</p>
            </div>
          </div>
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label fw-semibold">Rôle</label>
              <p class="mb-0">
                <span class="badge bg-info rounded-3 fw-semibold">{{ $invitation->role->name ?? 'N/A' }}</span>
              </p>
            </div>
          </div>
          <div class="col-12">
            <div class="mb-3">
              <label class="form-label fw-semibold">Projets</label>
              <p class="mb-0">
                @php
                  $invitationProjects = collect([]);
                  try {
                    // Essayer d'abord avec la relation chargée
                    if ($invitation->relationLoaded('projects')) {
                      $invitationProjects = $invitation->projects;
                    } 
                    // Sinon, essayer de charger la relation si la table existe
                    elseif (\Illuminate\Support\Facades\Schema::hasTable('invitation_project')) {
                      $invitation->load('projects');
                      $invitationProjects = $invitation->projects;
                    }
                    // Si la relation n'est pas disponible, utiliser project_id comme fallback
                    elseif ($invitation->project_id) {
                      $project = \App\Models\Project::find($invitation->project_id);
                      if ($project) {
                        $invitationProjects = collect([$project]);
                      }
                    }
                  } catch (\Exception $e) {
                    // Si tout échoue, utiliser l'ancienne colonne project_id
                    if ($invitation->project_id) {
                      $project = \App\Models\Project::find($invitation->project_id);
                      if ($project) {
                        $invitationProjects = collect([$project]);
                      }
                    }
                  }
                @endphp
                @if($invitationProjects && $invitationProjects->count() > 0)
                  <div class="d-flex flex-wrap gap-1">
                    @foreach($invitationProjects as $project)
                      <span class="badge bg-primary rounded-3 fw-semibold">{{ $project->name }}</span>
                    @endforeach
                  </div>
                @elseif($invitation->project_id)
                  @php
                    $project = \App\Models\Project::find($invitation->project_id);
                  @endphp
                  @if($project)
                    <span class="badge bg-primary rounded-3 fw-semibold">{{ $project->name }}</span>
                  @else
                    <span class="badge bg-secondary rounded-3 fw-semibold">Tous les projets de l'entreprise</span>
                  @endif
                @else
                  <span class="badge bg-secondary rounded-3 fw-semibold">Tous les projets de l'entreprise</span>
                @endif
              </p>
            </div>
          </div>
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label fw-semibold">Invité par</label>
              <p class="mb-0">{{ $invitation->inviter->name ?? 'N/A' }}</p>
            </div>
          </div>
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label fw-semibold">Date d'invitation</label>
              <p class="mb-0">{{ $invitation->created_at->format('d/m/Y H:i') }}</p>
            </div>
          </div>
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label fw-semibold">Date d'expiration</label>
              <p class="mb-0 {{ $invitation->isExpired() ? 'text-danger' : '' }}">
                {{ $invitation->expires_at->format('d/m/Y H:i') }}
              </p>
            </div>
          </div>
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label fw-semibold">Statut</label>
              <p class="mb-0">
                @php
                  $statusColors = [
                    'pending' => 'warning',
                    'accepted' => 'success',
                    'cancelled' => 'secondary',
                    'expired' => 'danger',
                  ];
                  $statusLabels = [
                    'pending' => 'En attente',
                    'accepted' => 'Acceptée',
                    'cancelled' => 'Annulée',
                    'expired' => 'Expirée',
                  ];
                  $status = $invitation->isExpired() ? 'expired' : $invitation->status;
                @endphp
                <span class="badge bg-{{ $statusColors[$status] ?? 'secondary' }} rounded-3 fw-semibold">
                  {{ $statusLabels[$status] ?? $invitation->status }}
                </span>
              </p>
            </div>
          </div>
          @if($invitation->accepted_at)
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label fw-semibold">Date d'acceptation</label>
                <p class="mb-0">{{ $invitation->accepted_at->format('d/m/Y H:i') }}</p>
              </div>
            </div>
          @endif
          @if($invitation->message)
            <div class="col-12">
              <div class="mb-3">
                <label class="form-label fw-semibold">Message</label>
                <p class="mb-0">{{ $invitation->message }}</p>
              </div>
            </div>
          @endif
          <div class="col-12">
            <div class="mb-3">
              <label class="form-label fw-semibold">Token</label>
              <p class="mb-0">
                <code class="text-muted">{{ $invitation->token }}</code>
              </p>
            </div>
          </div>
        </div>

        @if($invitation->status === 'pending' && !$invitation->isExpired())
          <div class="mt-4 d-flex gap-2">
            <form action="{{ route('invitations.resend', ['company' => $company, 'invitation' => $invitation]) }}" method="POST" class="d-inline">
              @csrf
              <button type="submit" class="btn btn-info">
                <i class="ti ti-refresh me-2"></i>Renvoyer l'invitation
              </button>
            </form>
            <form action="{{ route('invitations.destroy', ['company' => $company, 'invitation' => $invitation]) }}" method="POST" onsubmit="return confirm('Annuler cette invitation ?');" class="d-inline">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-danger">
                <i class="ti ti-trash me-2"></i>Annuler l'invitation
              </button>
            </form>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection

