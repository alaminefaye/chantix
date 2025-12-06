@extends('layouts.auth')

@section('title', 'Mot de passe oublié')

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
            <p class="text-center">Réinitialiser votre mot de passe</p>
            <form method="POST" action="{{ route('password.email') }}">
              @csrf
              @if (session('status'))
                <div class="alert alert-success">
                  {{ session('status') }}
                </div>
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
              <div class="mb-4">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required autofocus>
                @error('email')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="text-muted">Nous vous enverrons un lien de réinitialisation par email.</small>
              </div>
              <button type="submit" class="btn btn-primary w-100 py-8 fs-4 mb-4 rounded-2">Envoyer le lien</button>
              <div class="d-flex align-items-center justify-content-center">
                <a class="text-primary fw-bold" href="{{ route('login') }}">Retour à la connexion</a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

