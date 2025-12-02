@extends('layouts.app')

@section('title', 'Mes Entreprises')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h5 class="card-title fw-semibold mb-0">Mes Entreprises</h5>
          <a href="{{ route('companies.create') }}" class="btn btn-primary">
            <i class="ti ti-plus me-2"></i>Créer une entreprise
          </a>
        </div>

        @if(session('success'))
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        @endif

        <div class="table-responsive">
          <table class="table text-nowrap mb-0 align-middle">
            <thead class="text-dark fs-4">
              <tr>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Nom</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Email</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Ville</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Statut</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Actions</h6>
                </th>
              </tr>
            </thead>
            <tbody>
              @forelse($companies as $company)
                <tr>
                  <td class="border-bottom-0">
                    <h6 class="fw-semibold mb-0">{{ $company->name }}</h6>
                  </td>
                  <td class="border-bottom-0">
                    <p class="mb-0 fw-normal">{{ $company->email ?? '-' }}</p>
                  </td>
                  <td class="border-bottom-0">
                    <p class="mb-0 fw-normal">{{ $company->city ?? '-' }}</p>
                  </td>
                  <td class="border-bottom-0">
                    @if($company->is_active)
                      <span class="badge bg-success rounded-3 fw-semibold">Active</span>
                    @else
                      <span class="badge bg-secondary rounded-3 fw-semibold">Inactive</span>
                    @endif
                    @if(auth()->user()->current_company_id == $company->id)
                      <span class="badge bg-primary rounded-3 fw-semibold ms-2">Actuelle</span>
                    @endif
                  </td>
                  <td class="border-bottom-0">
                    <div class="d-flex align-items-center gap-2">
                      @if(auth()->user()->current_company_id != $company->id)
                        <form action="{{ route('companies.switch', $company) }}" method="POST">
                          @csrf
                          <button type="submit" class="btn btn-sm btn-outline-primary">Sélectionner</button>
                        </form>
                      @endif
                      <a href="{{ route('companies.show', $company) }}" class="btn btn-sm btn-info">Voir</a>
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="text-center py-4">
                    <p class="mb-0">Aucune entreprise trouvée. <a href="{{ route('companies.create') }}">Créer une entreprise</a></p>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

