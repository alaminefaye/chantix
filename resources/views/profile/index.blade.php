@extends('layouts.app')

@section('title', 'Mon Profil')

@section('content')
<div class="row">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h5 class="card-title fw-semibold mb-0">Mon Profil</h5>
          <a href="{{ route('profile.index') }}?edit=1" class="btn btn-primary">
            <i class="ti ti-edit me-2"></i>Modifier
          </a>
        </div>

        @if(request()->get('edit'))
          @include('profile.edit')
        @else
        <div class="row mb-4">
          <div class="col-md-3 text-center">
            @if(auth()->user()->avatar)
              <img src="{{ Storage::url(auth()->user()->avatar) }}" alt="Avatar" class="rounded-circle mb-3" width="120" height="120" style="object-fit: cover;">
            @else
              <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" style="width: 120px; height: 120px; font-size: 48px;">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
              </div>
            @endif
            <h6 class="fw-semibold mb-1">{{ auth()->user()->name }}</h6>
            <p class="text-muted mb-0">{{ auth()->user()->email }}</p>
            @if(!auth()->user()->hasVerifiedEmail())
              <div class="alert alert-warning mt-2" style="font-size: 0.8rem;">
                <i class="ti ti-alert-circle"></i> Email non vérifié
              </div>
            @endif
          </div>
          <div class="col-md-9">
            <h6 class="fw-semibold mb-3">Informations Personnelles</h6>
            <div class="row mb-3">
              <div class="col-md-6">
                <p class="mb-1"><strong>Nom complet:</strong></p>
                <p class="mb-3">{{ auth()->user()->name }}</p>
              </div>
              <div class="col-md-6">
                <p class="mb-1"><strong>Email:</strong></p>
                <p class="mb-3">{{ auth()->user()->email }}</p>
              </div>
            </div>
            @if(auth()->user()->phone)
              <div class="row mb-3">
                <div class="col-md-6">
                  <p class="mb-1"><strong>Téléphone:</strong></p>
                  <p class="mb-3">{{ auth()->user()->phone }}</p>
                </div>
              </div>
            @endif
          </div>
        </div>

        <hr>

        <div class="mb-4">
          <h6 class="fw-semibold mb-3">Entreprise Actuelle</h6>
          @if(auth()->user()->currentCompany)
            <div class="card bg-light">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <h6 class="fw-semibold mb-1">{{ auth()->user()->currentCompany->name }}</h6>
                    @if(auth()->user()->currentCompany->email)
                      <p class="text-muted mb-0">{{ auth()->user()->currentCompany->email }}</p>
                    @endif
                  </div>
                  <div class="text-end">
                    @if(auth()->user()->currentRole())
                      <span class="badge bg-primary rounded-3 fw-semibold fs-4">
                        {{ auth()->user()->currentRole()->display_name }}
                      </span>
                      <p class="text-muted mb-0 mt-1" style="font-size: 0.85rem;">
                        {{ auth()->user()->currentRole()->description }}
                      </p>
                    @else
                      <span class="badge bg-secondary">Aucun rôle</span>
                    @endif
                  </div>
                </div>
              </div>
            </div>
          @else
            <div class="alert alert-warning">
              <i class="ti ti-alert-circle me-2"></i>
              Aucune entreprise sélectionnée. <a href="{{ route('companies.index') }}">Sélectionner une entreprise</a>
            </div>
          @endif
        </div>

        <div class="mb-4">
          <h6 class="fw-semibold mb-3">Mes Entreprises</h6>
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>Entreprise</th>
                  <th>Rôle</th>
                  <th>Statut</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                @forelse(auth()->user()->companies as $company)
                  <tr>
                    <td>
                      <strong>{{ $company->name }}</strong>
                      @if($company->id == auth()->user()->current_company_id)
                        <span class="badge bg-success ms-2">Actuelle</span>
                      @endif
                    </td>
                    <td>
                      @php
                        $role = auth()->user()->roleInCompany($company->id);
                      @endphp
                      @if($role)
                        <span class="badge bg-primary">{{ $role->display_name }}</span>
                      @else
                        <span class="badge bg-secondary">Aucun rôle</span>
                      @endif
                    </td>
                    <td>
                      @if($company->pivot->is_active)
                        <span class="badge bg-success">Active</span>
                      @else
                        <span class="badge bg-secondary">Inactive</span>
                      @endif
                    </td>
                    <td>
                      @if($company->id != auth()->user()->current_company_id)
                        <form action="{{ route('companies.switch', $company) }}" method="POST" class="d-inline">
                          @csrf
                          <button type="submit" class="btn btn-sm btn-outline-primary">Sélectionner</button>
                        </form>
                      @endif
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="4" class="text-center">Aucune entreprise</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>

        <div class="alert alert-info">
          <h6 class="fw-semibold mb-2">
            <i class="ti ti-info-circle me-2"></i>À propos de votre rôle
          </h6>
          <p class="mb-0">
            <strong>Quand vous créez un compte :</strong> Vous êtes automatiquement assigné au rôle <strong>"Administrateur"</strong> 
            dans l'entreprise que vous créez. En tant qu'administrateur, vous avez accès à <strong>TOUTES</strong> les fonctionnalités de l'application.
          </p>
          <p class="mb-0 mt-2">
            <strong>Rôles disponibles :</strong> 
            <span class="badge bg-primary">Administrateur</span>
            <span class="badge bg-info">Chef de Chantier</span>
            <span class="badge bg-success">Ingénieur</span>
            <span class="badge bg-warning">Ouvrier</span>
            <span class="badge bg-danger">Comptable</span>
            <span class="badge bg-secondary">Superviseur</span>
          </p>
          <p class="mb-0 mt-2">
            <strong>💡 Astuce :</strong> Vous pouvez inviter d'autres utilisateurs dans votre entreprise et leur assigner différents rôles selon leurs besoins.
          </p>
        </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection

