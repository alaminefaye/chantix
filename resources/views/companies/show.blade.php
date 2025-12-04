@extends('layouts.app')

@section('title', $company->name)

@section('content')
<div class="row">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <div class="d-flex align-items-center gap-3">
            @if($company->logo)
              <img src="{{ Storage::url($company->logo) }}" alt="Logo" style="max-height: 60px;">
            @endif
            <h5 class="card-title fw-semibold mb-0">{{ $company->name }}</h5>
          </div>
          <div class="d-flex gap-2">
            @if(auth()->user()->hasRoleInCompany('admin', $company->id))
              <a href="{{ route('companies.edit', $company) }}" class="btn btn-warning">
                <i class="ti ti-edit me-2"></i>Modifier
              </a>
            @endif
            <a href="{{ route('companies.index') }}" class="btn btn-secondary">Retour</a>
          </div>
        </div>

        <div class="row mb-4">
          <div class="col-md-6">
            <h6 class="fw-semibold mb-2">Statut</h6>
            @if($company->is_active)
              <span class="badge bg-success rounded-3 fw-semibold fs-4">Active</span>
            @else
              <span class="badge bg-secondary rounded-3 fw-semibold fs-4">Inactive</span>
            @endif
          </div>
        </div>

        @if($company->email)
          <div class="mb-3">
            <h6 class="fw-semibold mb-2">Email</h6>
            <p class="mb-0">{{ $company->email }}</p>
          </div>
        @endif

        @if($company->phone)
          <div class="mb-3">
            <h6 class="fw-semibold mb-2">Téléphone</h6>
            <p class="mb-0">{{ $company->phone }}</p>
          </div>
        @endif

        @if($company->address)
          <div class="mb-3">
            <h6 class="fw-semibold mb-2">Adresse</h6>
            <p class="mb-0">{{ $company->address }}</p>
            @if($company->city || $company->country)
              <p class="mb-0 text-muted">{{ $company->city }}{{ $company->city && $company->country ? ', ' : '' }}{{ $company->country }}</p>
            @endif
          </div>
        @endif

        @if($company->siret)
          <div class="mb-3">
            <h6 class="fw-semibold mb-2">SIRET</h6>
            <p class="mb-0">{{ $company->siret }}</p>
          </div>
        @endif

        @if($company->description)
          <div class="mb-4">
            <h6 class="fw-semibold mb-2">Description</h6>
            <p class="mb-0">{{ $company->description }}</p>
          </div>
        @endif

        <div class="mb-4">
          <h6 class="fw-semibold mb-2">Projets</h6>
          <p class="mb-0">Cette entreprise a <strong>{{ $company->projects()->count() }}</strong> projet(s)</p>
        </div>

        @can('users.view')
          <hr class="my-4">
          <div class="d-flex gap-2">
            <a href="{{ route('users.index', $company) }}" class="btn btn-primary">
              <i class="ti ti-users me-2"></i>Gérer les utilisateurs
            </a>
          </div>
        @endcan
      </div>
    </div>
  </div>
</div>
@endsection

