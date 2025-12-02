@extends('layouts.app')

@section('title', 'Ajouter un employé')

@section('content')
<div class="row">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h5 class="card-title fw-semibold mb-0">Ajouter un employé</h5>
          <a href="{{ route('employees.index') }}" class="btn btn-secondary">Retour</a>
        </div>

        @if ($errors->any())
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Erreur !</strong> Veuillez corriger les erreurs suivantes :
            <ul class="mb-0 mt-2">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        @endif

        @if(session('error'))
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        @endif

        <form action="{{ route('employees.store') }}" method="POST" id="employeeForm" novalidate>
          @csrf

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="first_name" class="form-label">Prénom <span class="text-danger">*</span></label>
              <input type="text" class="form-control @error('first_name') is-invalid @enderror" id="first_name" name="first_name" value="{{ old('first_name') }}" required>
              @error('first_name')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6 mb-3">
              <label for="last_name" class="form-label">Nom <span class="text-danger">*</span></label>
              <input type="text" class="form-control @error('last_name') is-invalid @enderror" id="last_name" name="last_name" value="{{ old('last_name') }}" required>
              @error('last_name')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="email" class="form-label">Email</label>
              <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}">
              @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6 mb-3">
              <label for="phone" class="form-label">Téléphone</label>
              <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}">
              @error('phone')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="position" class="form-label">Poste</label>
              <input type="text" class="form-control @error('position') is-invalid @enderror" id="position" name="position" value="{{ old('position') }}" placeholder="Ex: Maçon, Électricien, Plombier...">
              @error('position')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6 mb-3">
              <label for="employee_number" class="form-label">Numéro d'employé</label>
              <input type="text" class="form-control bg-light" id="employee_number" value="Généré automatiquement" readonly disabled>
              <small class="text-muted">Le numéro d'employé sera généré automatiquement lors de la création</small>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="hire_date" class="form-label">Date d'embauche</label>
              <input type="date" class="form-control @error('hire_date') is-invalid @enderror" id="hire_date" name="hire_date" value="{{ old('hire_date') }}">
              @error('hire_date')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6 mb-3">
              <label for="hourly_rate" class="form-label">Taux horaire (FCFA)</label>
              <input type="number" step="0.01" min="0" class="form-control @error('hourly_rate') is-invalid @enderror" id="hourly_rate" name="hourly_rate" value="{{ old('hourly_rate') }}">
              @error('hourly_rate')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div class="mb-3">
            <label for="address" class="form-label">Adresse</label>
            <input type="text" class="form-control @error('address') is-invalid @enderror" id="address" name="address" value="{{ old('address') }}">
            @error('address')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="city" class="form-label">Ville</label>
              <input type="text" class="form-control @error('city') is-invalid @enderror" id="city" name="city" value="{{ old('city') }}">
              @error('city')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6 mb-3">
              <label for="country" class="form-label">Pays</label>
              <input type="text" class="form-control @error('country') is-invalid @enderror" id="country" name="country" value="{{ old('country') }}">
              @error('country')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="birth_date" class="form-label">Date de naissance</label>
              <input type="date" class="form-control @error('birth_date') is-invalid @enderror" id="birth_date" name="birth_date" value="{{ old('birth_date') }}">
              @error('birth_date')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6 mb-3">
              <label for="id_number" class="form-label">Numéro de pièce d'identité</label>
              <input type="text" class="form-control @error('id_number') is-invalid @enderror" id="id_number" name="id_number" value="{{ old('id_number') }}">
              @error('id_number')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div class="mb-3">
            <label for="notes" class="form-label">Notes</label>
            <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
            @error('notes')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
              <label class="form-check-label" for="is_active">
                Employé actif
              </label>
            </div>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary" id="submitBtn">
              <span class="spinner-border spinner-border-sm d-none me-2" id="spinner" role="status" aria-hidden="true"></span>
              <span id="btnText">Créer l'employé</span>
            </button>
            <a href="{{ route('employees.index') }}" class="btn btn-secondary">Annuler</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
(function() {
  'use strict';
  
  const form = document.getElementById('employeeForm');
  const submitBtn = document.getElementById('submitBtn');
  const spinner = document.getElementById('spinner');
  const btnText = document.getElementById('btnText');
  
  if (form && submitBtn) {
    form.addEventListener('submit', function(e) {
      // Ne pas empêcher la soumission, juste afficher le feedback
      if (submitBtn && !submitBtn.disabled) {
        submitBtn.disabled = true;
        if (spinner) spinner.classList.remove('d-none');
        if (btnText) btnText.textContent = 'Création en cours...';
      }
    });
  }
  
  // Scroll vers les erreurs si présentes
  @if ($errors->any())
    setTimeout(function() {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }, 100);
  @endif
})();
</script>
@endpush
@endsection
