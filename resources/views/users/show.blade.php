@extends('layouts.app')

@section('title', 'Détails utilisateur - ' . $user->name)

@section('content')
<div class="row">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h5 class="card-title fw-semibold mb-0">Détails utilisateur - {{ $user->name }}</h5>
          <div class="d-flex gap-2">
            @can('users.update')
            <a href="{{ route('users.edit', ['company' => $company, 'user' => $user]) }}" class="btn btn-warning">
              <i class="ti ti-edit me-2"></i>Modifier
            </a>
            @endcan
            <a href="{{ route('users.index', $company) }}" class="btn btn-secondary">Retour</a>
          </div>
        </div>

        <div class="row mb-4">
          <div class="col-md-6">
            <h6 class="fw-semibold mb-2">Nom</h6>
            <p class="mb-0">{{ $user->name }}</p>
          </div>
          <div class="col-md-6">
            <h6 class="fw-semibold mb-2">Email</h6>
            <p class="mb-0">{{ $user->email }}</p>
          </div>
        </div>

        <div class="row mb-4">
          <div class="col-md-6">
            <h6 class="fw-semibold mb-2">Rôle dans l'entreprise</h6>
            <span class="badge bg-info rounded-3 fw-semibold">
              {{ $role->name ?? 'N/A' }}
            </span>
          </div>
          <div class="col-md-6">
            <h6 class="fw-semibold mb-2">Statut</h6>
            <span class="badge bg-{{ $pivot->is_active ? 'success' : 'danger' }} rounded-3 fw-semibold">
              {{ $pivot->is_active ? 'Actif' : 'Inactif' }}
            </span>
          </div>
        </div>

        <div class="row mb-4">
          <div class="col-md-6">
            <h6 class="fw-semibold mb-2">Date d'ajout</h6>
            <p class="mb-0">{{ $pivot->joined_at->format('d/m/Y H:i') }}</p>
          </div>
          <div class="col-md-6">
            <h6 class="fw-semibold mb-2">Vérifié</h6>
            <span class="badge bg-{{ $user->is_verified ? 'success' : 'warning' }} rounded-3 fw-semibold">
              {{ $user->is_verified ? 'Oui' : 'Non' }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

