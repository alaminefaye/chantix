@extends('layouts.auth')

@section('title', 'Register')

@section('content')
<div
  class="position-relative overflow-hidden radial-gradient min-vh-100 d-flex align-items-center justify-content-center">
  <div class="d-flex align-items-center justify-content-center w-100">
    <div class="row justify-content-center w-100">
      <div class="col-md-8 col-lg-6 col-xxl-3">
        <div class="card mb-0">
          <div class="card-body">
            <a href="{{ route('dashboard') }}" class="text-nowrap logo-img text-center d-block py-3 w-100">
              <img src="{{ asset('assets/images/logos/logo.png') }}" width="180" alt="Chantix Logo">
            </a>
            @if(isset($invitation) && $invitation)
              <div class="alert alert-info mb-3">
                <h6 class="fw-semibold mb-2">Invitation reçue</h6>
                <p class="mb-0">Vous avez été invité à rejoindre <strong>{{ $invitation->company->name }}</strong> en tant que <strong>{{ $invitation->role->name ?? 'Membre' }}</strong>.</p>
              </div>
            @endif

            <p class="text-center">{{ isset($invitation) && $invitation ? 'Créer votre compte pour accepter l\'invitation' : 'Créer votre compte Chantix' }}</p>
            <form method="POST" action="{{ route('register') }}">
              @csrf
              @if(isset($invitation) && $invitation)
                <input type="hidden" name="token" value="{{ $invitation->token }}">
              @endif
              @if ($errors->any())
                <div class="alert alert-danger">
                  <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                      <li>{{ $error }}</li>
                    @endforeach
                  </ul>
                </div>
              @endif
              <div class="mb-3">
                <label for="name" class="form-label">Nom complet</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required autofocus>
                @error('name')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', isset($invitation) && $invitation ? $invitation->email : '') }}" required {{ isset($invitation) && $invitation ? 'readonly' : '' }}>
                @error('email')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              @if(!isset($invitation) || !$invitation)
                <div class="mb-3">
                  <label for="company_name" class="form-label">Nom de l'entreprise</label>
                  <input type="text" class="form-control @error('company_name') is-invalid @enderror" id="company_name" name="company_name" value="{{ old('company_name') }}" required>
                  @error('company_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
              @endif
              <div class="mb-3">
                <label for="password" class="form-label">Mot de passe</label>
                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required>
                @error('password')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="mb-4">
                <label for="password_confirmation" class="form-label">Confirmer le mot de passe</label>
                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
              </div>
              <button type="submit" class="btn btn-primary w-100 py-8 fs-4 mb-4 rounded-2">S'inscrire</button>
              <div class="d-flex align-items-center justify-content-center">
                <p class="fs-4 mb-0 fw-bold">Déjà un compte ?</p>
                <a class="text-primary fw-bold ms-2" href="{{ route('login') }}">Se connecter</a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection


