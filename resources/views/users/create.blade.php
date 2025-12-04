@extends('layouts.app')

@section('title', 'Créer un utilisateur - ' . $company->name)

@section('content')
<div class="row">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h5 class="card-title fw-semibold mb-0">Créer un utilisateur - {{ $company->name }}</h5>
          <a href="{{ route('users.index', $company) }}" class="btn btn-secondary">Retour</a>
        </div>

        <form action="{{ route('users.store', $company) }}" method="POST">
          @csrf

          <div class="mb-3">
            <label for="name" class="form-label">Nom complet <span class="text-danger">*</span></label>
            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required placeholder="Prénom Nom">
            @error('name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required placeholder="exemple@email.com">
            <small class="text-muted">Si l'utilisateur existe déjà, il sera ajouté directement à l'entreprise</small>
            @error('email')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label for="password" class="form-label">Mot de passe <span class="text-danger">*</span></label>
            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required placeholder="Minimum 8 caractères">
            <small class="text-muted">Si l'utilisateur existe déjà, ce champ sera ignoré</small>
            @error('password')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label for="role_id" class="form-label">Rôle <span class="text-danger">*</span></label>
            <select class="form-select @error('role_id') is-invalid @enderror" id="role_id" name="role_id" required>
              <option value="">Sélectionner un rôle</option>
              @foreach($roles as $role)
                <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                  {{ $role->name }} - {{ $role->description ?? '' }}
                </option>
              @endforeach
            </select>
            @error('role_id')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="alert alert-info">
            <h6 class="fw-semibold mb-2">
              <i class="ti ti-info-circle me-2"></i>Information
            </h6>
            <ul class="mb-0">
              <li>L'utilisateur sera créé ou ajouté immédiatement à l'entreprise</li>
              <li>Si l'utilisateur existe déjà, il sera ajouté directement avec le rôle sélectionné</li>
              <li>Si l'utilisateur n'existe pas, un compte sera créé avec le mot de passe fourni</li>
            </ul>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="ti ti-user-plus me-2"></i>Créer l'utilisateur
            </button>
            <a href="{{ route('users.index', $company) }}" class="btn btn-secondary">Annuler</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

