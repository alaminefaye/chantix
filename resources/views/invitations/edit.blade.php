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

        <form action="{{ route('invitations.update', ['company' => $company, 'invitation' => $invitation]) }}" method="POST">
          @csrf
          @method('PUT')

          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $invitation->email) }}" required>
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

