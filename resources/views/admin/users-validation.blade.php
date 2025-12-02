@extends('layouts.app')

@section('title', 'Validation des Utilisateurs')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h5 class="card-title fw-semibold mb-0">Validation des Utilisateurs</h5>
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

        <!-- Utilisateurs en attente -->
        <div class="mb-5">
          <h6 class="fw-semibold mb-3 text-warning">
            <i class="ti ti-clock me-2"></i>Utilisateurs en attente de validation ({{ $pendingUsers->total() }})
          </h6>
          
          @if($pendingUsers->count() > 0)
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
                      <h6 class="fw-semibold mb-0">Entreprise</h6>
                    </th>
                    <th class="border-bottom-0">
                      <h6 class="fw-semibold mb-0">Date d'inscription</h6>
                    </th>
                    <th class="border-bottom-0">
                      <h6 class="fw-semibold mb-0">Actions</h6>
                    </th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($pendingUsers as $pendingUser)
                    <tr>
                      <td class="border-bottom-0">
                        <h6 class="fw-semibold mb-0">{{ $pendingUser->name }}</h6>
                      </td>
                      <td class="border-bottom-0">
                        <p class="mb-0 fw-normal">{{ $pendingUser->email }}</p>
                      </td>
                      <td class="border-bottom-0">
                        @if($pendingUser->companies->count() > 0)
                          @foreach($pendingUser->companies as $company)
                            <span class="badge bg-info me-1">{{ $company->name }}</span>
                          @endforeach
                        @else
                          <span class="text-muted">-</span>
                        @endif
                      </td>
                      <td class="border-bottom-0">
                        <p class="mb-0 fw-normal">{{ $pendingUser->created_at->format('d/m/Y H:i') }}</p>
                      </td>
                      <td class="border-bottom-0">
                        <div class="d-flex align-items-center gap-2">
                          <form action="{{ route('admin.users.verify', $pendingUser) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Valider cet utilisateur ?');">
                              <i class="ti ti-check me-1"></i>Valider
                            </button>
                          </form>
                          <form action="{{ route('admin.users.reject', $pendingUser) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Rejeter et supprimer cet utilisateur ? Cette action est irréversible.');">
                              <i class="ti ti-x me-1"></i>Rejeter
                            </button>
                          </form>
                        </div>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
            
            @if($pendingUsers->hasPages())
              <div class="mt-4">
                {{ $pendingUsers->links() }}
              </div>
            @endif
          @else
            <div class="alert alert-info">
              <i class="ti ti-info-circle me-2"></i>Aucun utilisateur en attente de validation.
            </div>
          @endif
        </div>

        <!-- Utilisateurs validés -->
        <div>
          <h6 class="fw-semibold mb-3 text-success">
            <i class="ti ti-check-circle me-2"></i>Utilisateurs validés ({{ $verifiedUsers->total() }})
          </h6>
          
          @if($verifiedUsers->count() > 0)
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
                      <h6 class="fw-semibold mb-0">Entreprise</h6>
                    </th>
                    <th class="border-bottom-0">
                      <h6 class="fw-semibold mb-0">Date de validation</h6>
                    </th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($verifiedUsers as $verifiedUser)
                    <tr>
                      <td class="border-bottom-0">
                        <h6 class="fw-semibold mb-0">{{ $verifiedUser->name }}</h6>
                      </td>
                      <td class="border-bottom-0">
                        <p class="mb-0 fw-normal">{{ $verifiedUser->email }}</p>
                      </td>
                      <td class="border-bottom-0">
                        @if($verifiedUser->companies->count() > 0)
                          @foreach($verifiedUser->companies as $company)
                            <span class="badge bg-info me-1">{{ $company->name }}</span>
                          @endforeach
                        @else
                          <span class="text-muted">-</span>
                        @endif
                      </td>
                      <td class="border-bottom-0">
                        <p class="mb-0 fw-normal">{{ $verifiedUser->updated_at->format('d/m/Y H:i') }}</p>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
            
            @if($verifiedUsers->hasPages())
              <div class="mt-4">
                {{ $verifiedUsers->links() }}
              </div>
            @endif
          @else
            <div class="alert alert-info">
              <i class="ti ti-info-circle me-2"></i>Aucun utilisateur validé.
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

