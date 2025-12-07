@extends('layouts.app')

@section('title', 'Invitations - ' . $company->name)

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h5 class="card-title fw-semibold mb-0">Invitations - {{ $company->name }}</h5>
          <div class="d-flex gap-2">
            <a href="{{ route('invitations.create', $company) }}" class="btn btn-primary">
              <i class="ti ti-user-plus me-2"></i>Inviter un collaborateur
            </a>
            <a href="{{ route('companies.show', $company) }}" class="btn btn-secondary">Retour</a>
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

        <div class="table-responsive">
          <table class="table text-nowrap mb-0 align-middle">
            <thead class="text-dark fs-4">
              <tr>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Email</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Rôle</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Projets</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Invité par</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Date</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Expiration</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Statut</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Actions</h6>
                </th>
              </tr>
            </thead>
            <tbody>
              @forelse($invitations as $invitation)
                <tr>
                  <td class="border-bottom-0">
                    <p class="mb-0 fw-normal">{{ $invitation->email }}</p>
                  </td>
                  <td class="border-bottom-0">
                    <span class="badge bg-info rounded-3 fw-semibold">{{ $invitation->role->name ?? 'N/A' }}</span>
                  </td>
                  <td class="border-bottom-0">
                    @php
                      $invitationProjects = collect([]);
                      // Prioriser toujours la relation many-to-many projects
                      if (\Illuminate\Support\Facades\Schema::hasTable('invitation_project')) {
                        try {
                          // Charger la relation si elle n'est pas déjà chargée
                          if (!$invitation->relationLoaded('projects')) {
                            $invitation->load('projects');
                          }
                          // Récupérer tous les projets de la relation many-to-many
                          $invitationProjects = $invitation->projects;
                        } catch (\Exception $e) {
                          \Log::warning('Erreur lors du chargement des projets de l\'invitation: ' . $e->getMessage());
                        }
                      }
                      
                      // Fallback: utiliser project_id seulement si la relation many-to-many est vide
                      if ($invitationProjects->isEmpty() && $invitation->project_id) {
                        $project = \App\Models\Project::find($invitation->project_id);
                        if ($project) {
                          $invitationProjects = collect([$project]);
                        }
                      }
                    @endphp
                    @if($invitationProjects && $invitationProjects->count() > 0)
                      <div class="d-flex flex-wrap gap-1">
                        @foreach($invitationProjects as $project)
                          <span class="badge bg-primary rounded-3 fw-semibold" title="{{ $project->name }}">
                            {{ Str::limit($project->name, 20) }}
                          </span>
                        @endforeach
                      </div>
                    @else
                      <span class="badge bg-secondary rounded-3 fw-semibold">Tous les projets</span>
                    @endif
                  </td>
                  <td class="border-bottom-0">
                    <p class="mb-0 fw-normal">{{ $invitation->inviter->name ?? 'N/A' }}</p>
                  </td>
                  <td class="border-bottom-0">
                    <p class="mb-0 fw-normal">{{ $invitation->created_at->format('d/m/Y H:i') }}</p>
                  </td>
                  <td class="border-bottom-0">
                    <p class="mb-0 fw-normal {{ $invitation->isExpired() ? 'text-danger' : '' }}">
                      {{ $invitation->expires_at->format('d/m/Y') }}
                    </p>
                  </td>
                  <td class="border-bottom-0">
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
                  </td>
                  <td class="border-bottom-0">
                    <div class="d-flex align-items-center gap-2">
                      <a href="{{ route('invitations.show', ['company' => $company, 'invitation' => $invitation]) }}" class="btn btn-sm btn-info" title="Voir">
                        <i class="ti ti-eye"></i>
                      </a>
                      @if($invitation->status === 'pending' && !$invitation->isExpired())
                        <a href="{{ route('invitations.edit', ['company' => $company, 'invitation' => $invitation]) }}" class="btn btn-sm btn-warning" title="Modifier">
                          <i class="ti ti-edit"></i>
                        </a>
                        <form action="{{ route('invitations.resend', ['company' => $company, 'invitation' => $invitation]) }}" method="POST" class="d-inline">
                          @csrf
                          <button type="submit" class="btn btn-sm btn-info" title="Renvoyer l'invitation">
                            <i class="ti ti-refresh"></i>
                          </button>
                        </form>
                      @elseif($invitation->status === 'pending' && $invitation->isExpired())
                        <button type="button" class="btn btn-sm btn-secondary" title="Cette invitation a expiré et ne peut plus être modifiée" disabled>
                          <i class="ti ti-edit"></i>
                        </button>
                      @else
                        <a href="{{ route('invitations.edit', ['company' => $company, 'invitation' => $invitation]) }}" class="btn btn-sm btn-warning" title="Modifier (pour mettre à jour le projet associé)">
                          <i class="ti ti-edit"></i>
                        </a>
                      @endif
                      <form action="{{ route('invitations.destroy', ['company' => $company, 'invitation' => $invitation]) }}" method="POST" onsubmit="return confirm('{{ $invitation->status === 'pending' ? 'Annuler cette invitation ?' : 'Supprimer cette invitation ?' }}');" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger" title="Supprimer">
                          <i class="ti ti-trash"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="8" class="text-center py-4">
                    <p class="mb-0">Aucune invitation. <a href="{{ route('invitations.create', $company) }}">Inviter un collaborateur</a></p>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        @if($invitations->hasPages())
          <div class="mt-4">
            {{ $invitations->links() }}
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection

