@extends('layouts.app')

@section('title', 'Importer des employés')

@section('content')
<div class="row">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h5 class="card-title fw-semibold mb-0">Importer des employés depuis Excel</h5>
          <a href="{{ route('employees.index') }}" class="btn btn-secondary">Retour</a>
        </div>

        @if(session('error'))
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        @endif

        @if(session('import_errors') && count(session('import_errors')) > 0)
          <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <h6 class="fw-semibold mb-2">Erreurs lors de l'import :</h6>
            <ul class="mb-0">
              @foreach(session('import_errors') as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        @endif

        <div class="alert alert-info mb-4">
          <h6 class="fw-semibold mb-2">
            <i class="ti ti-info-circle me-2"></i>Instructions
          </h6>
          <ol class="mb-0">
            <li>Téléchargez le template Excel ci-dessous</li>
            <li>Remplissez le fichier avec vos données (la première ligne contient les en-têtes)</li>
            <li>Colonnes requises : <strong>Prénom</strong>, <strong>Nom</strong></li>
            <li>Colonnes optionnelles : Email, Téléphone, Poste, Taux horaire, Adresse, Ville, Pays</li>
            <li>Uploadez le fichier rempli</li>
          </ol>
        </div>

        <form action="{{ route('employees.import.store') }}" method="POST" enctype="multipart/form-data">
          @csrf

          <div class="mb-3">
            <label for="file" class="form-label">Fichier Excel (.xlsx, .xls, .csv) <span class="text-danger">*</span></label>
            <input type="file" class="form-control @error('file') is-invalid @enderror" id="file" name="file" accept=".xlsx,.xls,.csv" required>
            <small class="text-muted">Taille maximale : 10 MB</small>
            @error('file')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="ti ti-upload me-2"></i>Importer
            </button>
            <a href="{{ route('employees.template.download') }}" class="btn btn-outline-primary">
              <i class="ti ti-download me-2"></i>Télécharger le template
            </a>
            <a href="{{ route('employees.index') }}" class="btn btn-secondary">Annuler</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

