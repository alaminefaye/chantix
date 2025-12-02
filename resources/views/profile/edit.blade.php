@extends('layouts.app')

@section('title', 'Modifier mon profil')

@section('content')
<div class="row">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h5 class="card-title fw-semibold mb-0">Modifier mon profil</h5>
          <a href="{{ route('profile.index') }}" class="btn btn-secondary">Retour</a>
        </div>

        @if(session('success'))
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        @endif

        <ul class="nav nav-tabs mb-4" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab">
              Informations personnelles
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#password" type="button" role="tab">
              Mot de passe
            </button>
          </li>
        </ul>

        <div class="tab-content">
          <!-- Onglet Informations -->
          <div class="tab-pane fade show active" id="profile" role="tabpanel">
            <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
              @csrf
              @method('PUT')

              <div class="mb-3 text-center">
                @if(auth()->user()->avatar)
                  <img src="{{ Storage::url(auth()->user()->avatar) }}" alt="Avatar" class="rounded-circle mb-3" width="120" height="120" style="object-fit: cover;">
                @else
                  <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" width="120" height="120" style="width: 120px; height: 120px; font-size: 48px;">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                  </div>
                @endif
                <div>
                  <label for="avatar" class="btn btn-sm btn-outline-primary">
                    <i class="ti ti-upload me-1"></i>Changer la photo
                  </label>
                  <input type="file" class="d-none" id="avatar" name="avatar" accept="image/*" onchange="this.form.submit()">
                </div>
              </div>

              <div class="mb-3">
                <label for="name" class="form-label">Nom complet <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', auth()->user()->name) }}" required>
                @error('name')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="mb-3">
                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', auth()->user()->email) }}" required>
                @error('email')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="mb-3">
                <label for="phone" class="form-label">Téléphone</label>
                <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', auth()->user()->phone) }}">
                @error('phone')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Enregistrer</button>
                <a href="{{ route('profile.index') }}" class="btn btn-secondary">Annuler</a>
              </div>
            </form>
          </div>

          <!-- Onglet Mot de passe -->
          <div class="tab-pane fade" id="password" role="tabpanel">
            <form action="{{ route('profile.password.update') }}" method="POST">
              @csrf
              @method('PUT')

              <div class="mb-3">
                <label for="current_password" class="form-label">Mot de passe actuel <span class="text-danger">*</span></label>
                <input type="password" class="form-control @error('current_password') is-invalid @enderror" id="current_password" name="current_password" required>
                @error('current_password')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="mb-3">
                <label for="password" class="form-label">Nouveau mot de passe <span class="text-danger">*</span></label>
                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required>
                @error('password')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="text-muted">Minimum 8 caractères</small>
              </div>

              <div class="mb-3">
                <label for="password_confirmation" class="form-label">Confirmer le nouveau mot de passe <span class="text-danger">*</span></label>
                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
              </div>

              <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Changer le mot de passe</button>
                <a href="{{ route('profile.index') }}" class="btn btn-secondary">Annuler</a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

