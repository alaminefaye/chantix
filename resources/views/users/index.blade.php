@extends('layouts.app')

@section('title', 'Utilisateurs - ' . $company->name)

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h5 class="card-title fw-semibold mb-0">Utilisateurs - {{ $company->name }}</h5>
          <div class="d-flex gap-2">
            @can('users.create')
            <a href="{{ route('users.create', $company) }}" class="btn btn-primary">
              <i class="ti ti-user-plus me-2"></i>Créer un utilisateur
            </a>
            @endcan
            <a href="{{ route('companies.show', $company) }}" class="btn btn-secondary">Retour</a>
          </div>
        </div>

        @if(session('success'))
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        @endif

        @if(session('error'))
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
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
                  <h6 class="fw-semibold mb-0">Rôle</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Date d'ajout</h6>
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
              @forelse($users as $userItem)
                <tr>
                  <td class="border-bottom-0">
                    <p class="mb-0 fw-normal">{{ $userItem->name }}</p>
                  </td>
                  <td class="border-bottom-0">
                    <p class="mb-0 fw-normal">{{ $userItem->email }}</p>
                  </td>
                  <td class="border-bottom-0">
                    <span class="badge bg-info rounded-3 fw-semibold">
                      {{ $userItem->companyRole->name ?? 'N/A' }}
                    </span>
                  </td>
                  <td class="border-bottom-0">
                    <p class="mb-0 fw-normal">{{ $userItem->pivot->joined_at->format('d/m/Y') }}</p>
                  </td>
                  <td class="border-bottom-0">
                    <span class="badge bg-{{ $userItem->pivot->is_active ? 'success' : 'danger' }} rounded-3 fw-semibold">
                      {{ $userItem->pivot->is_active ? 'Actif' : 'Inactif' }}
                    </span>
                  </td>
                  <td class="border-bottom-0">
                    <div class="d-flex align-items-center gap-2">
                      @can('users.view')
                      <a href="{{ route('users.show', ['company' => $company, 'user' => $userItem]) }}" class="btn btn-sm btn-info" title="Voir">
                        <i class="ti ti-eye"></i>
                      </a>
                      @endcan
                      @can('users.update')
                      <a href="{{ route('users.edit', ['company' => $company, 'user' => $userItem]) }}" class="btn btn-sm btn-warning" title="Modifier">
                        <i class="ti ti-edit"></i>
                      </a>
                      @endcan
                      @can('users.delete')
                      @if($userItem->id !== Auth::id())
                      <form action="{{ route('users.destroy', ['company' => $company, 'user' => $userItem]) }}" method="POST" onsubmit="return confirm('Retirer cet utilisateur de l\'entreprise ?');" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger" title="Retirer">
                          <i class="ti ti-trash"></i>
                        </button>
                      </form>
                      @endif
                      @endcan
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="text-center py-4">
                    <p class="mb-0">Aucun utilisateur. @can('users.create')<a href="{{ route('users.create', $company) }}">Créer un utilisateur</a>@endcan</p>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        @if($users->hasPages())
          <div class="mt-4">
            {{ $users->links() }}
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection

