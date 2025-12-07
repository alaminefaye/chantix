@extends('layouts.app')

@section('title', 'Modifier l\'invitation - ' . $company->name)

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h5 class="card-title fw-semibold mb-0">Modifier l'invitation</h5>
          <a href="{{ route('invitations.index', $company) }}" class="btn btn-secondary">
            <i class="ti ti-arrow-left me-2"></i>Retour
          </a>
        </div>

        @if(session('error'))
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        @endif

        @if($errors->any())
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
              @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        @endif

        @if($invitation->status === 'accepted')
          <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="ti ti-info-circle me-2"></i>
            <strong>Invitation acceptée :</strong> La modification des projets mettra à jour automatiquement l'association de l'utilisateur avec les projets.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        @endif

        <form action="{{ route('invitations.update', ['company' => $company, 'invitation' => $invitation]) }}" method="POST">
          @csrf
          @method('PUT')

          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                @if($invitation->status === 'accepted')
                  <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $invitation->email) }}" required readonly>
                  <small class="text-muted">L'email ne peut pas être modifié pour une invitation acceptée.</small>
                @else
                  <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $invitation->email) }}" required>
                @endif
                @error('email')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label for="role_id" class="form-label">Rôle <span class="text-danger">*</span></label>
                <select class="form-select @error('role_id') is-invalid @enderror" id="role_id" name="role_id" required>
                  <option value="">Sélectionner un rôle</option>
                  @foreach($roles as $role)
                    <option value="{{ $role->id }}" {{ old('role_id', $invitation->role_id) == $role->id ? 'selected' : '' }}>
                      {{ $role->display_name ?? $role->name }}
                    </option>
                  @endforeach
                </select>
                @error('role_id')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label for="project_ids" class="form-label">Projets <span class="text-muted">(optionnel)</span></label>
                <select class="form-select @error('project_ids') is-invalid @enderror" id="project_ids" name="project_ids[]" multiple size="5">
                  @foreach($projects as $project)
                    <option value="{{ $project->id }}" {{ in_array($project->id, old('project_ids', $invitation->projects->pluck('id')->toArray())) ? 'selected' : '' }}>
                      {{ $project->name }}
                    </option>
                  @endforeach
                </select>
                <small class="text-muted">Sélectionnez un ou plusieurs projets. Si aucun projet n'est sélectionné, l'utilisateur aura accès à tous les projets de l'entreprise. Maintenez Ctrl (ou Cmd sur Mac) pour sélectionner plusieurs projets.</small>
                @error('project_ids')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
            <div class="col-12">
              <div class="mb-3">
                <label for="message" class="form-label">Message (optionnel)</label>
                <textarea class="form-control @error('message') is-invalid @enderror" id="message" name="message" rows="3" maxlength="1000">{{ old('message', $invitation->message) }}</textarea>
                @error('message')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="text-muted">Message personnalisé à inclure dans l'email d'invitation (max 1000 caractères)</small>
              </div>
            </div>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="ti ti-check me-2"></i>Enregistrer les modifications
            </button>
            <a href="{{ route('invitations.index', $company) }}" class="btn btn-secondary">
              Annuler
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

