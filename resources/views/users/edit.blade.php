@extends('layouts.app')

@section('title', 'Modifier utilisateur - ' . $user->name)

@section('content')
<div class="row">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h5 class="card-title fw-semibold mb-0">Modifier utilisateur - {{ $user->name }}</h5>
          <a href="{{ route('users.index', $company) }}" class="btn btn-secondary">Retour</a>
        </div>

        <form action="{{ route('users.update', ['company' => $company, 'user' => $user]) }}" method="POST">
          @csrf
          @method('PUT')

          <div class="mb-3">
            <label for="name" class="form-label">Nom complet <span class="text-danger">*</span></label>
            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required>
            @error('name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}" required>
            @error('email')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label for="password" class="form-label">Nouveau mot de passe (laisser vide pour ne pas changer)</label>
            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" placeholder="Minimum 8 caractères">
            @error('password')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label for="role_id" class="form-label">Rôle <span class="text-danger">*</span></label>
            <select class="form-select @error('role_id') is-invalid @enderror" id="role_id" name="role_id" required>
              <option value="">Sélectionner un rôle</option>
              @foreach($roles as $role)
                <option value="{{ $role->id }}" {{ old('role_id', $currentRole->id ?? null) == $role->id ? 'selected' : '' }}>
                  {{ $role->name }} - {{ $role->description ?? '' }}
                </option>
              @endforeach
            </select>
            @error('role_id')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $pivot->is_active) ? 'checked' : '' }}>
              <label class="form-check-label" for="is_active">
                Actif
              </label>
            </div>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="ti ti-check me-2"></i>Enregistrer
            </button>
            <a href="{{ route('users.index', $company) }}" class="btn btn-secondary">Annuler</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

