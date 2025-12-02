@extends('layouts.app')

@section('title', 'Employés')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h5 class="card-title fw-semibold mb-0">Employés</h5>
          <div class="d-flex gap-2">
            <a href="{{ route('employees.import') }}" class="btn btn-info">
              <i class="ti ti-upload me-2"></i>Importer Excel
            </a>
            <a href="{{ route('employees.create') }}" class="btn btn-primary">
              <i class="ti ti-plus me-2"></i>Ajouter un employé
            </a>
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
                  <h6 class="fw-semibold mb-0">Poste</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Contact</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Numéro</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Taux horaire</h6>
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
              @forelse($employees as $employee)
                <tr>
                  <td class="border-bottom-0">
                    <h6 class="fw-semibold mb-0">{{ $employee->full_name }}</h6>
                  </td>
                  <td class="border-bottom-0">
                    @if($employee->position)
                      <span class="badge bg-info rounded-3 fw-semibold">{{ $employee->position }}</span>
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>
                  <td class="border-bottom-0">
                    @if($employee->email)
                      <p class="mb-0 fw-normal">{{ $employee->email }}</p>
                    @endif
                    @if($employee->phone)
                      <p class="mb-0 fw-normal text-muted" style="font-size: 0.85rem;">{{ $employee->phone }}</p>
                    @endif
                  </td>
                  <td class="border-bottom-0">
                    @if($employee->employee_number)
                      <p class="mb-0 fw-normal">{{ $employee->employee_number }}</p>
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>
                  <td class="border-bottom-0">
                    @if($employee->hourly_rate)
                      <h6 class="fw-semibold mb-0">{{ number_format($employee->hourly_rate, 2, ',', ' ') }} FCFA/h</h6>
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>
                  <td class="border-bottom-0">
                    @if($employee->is_active)
                      <span class="badge bg-success rounded-3 fw-semibold">Actif</span>
                    @else
                      <span class="badge bg-secondary rounded-3 fw-semibold">Inactif</span>
                    @endif
                  </td>
                  <td class="border-bottom-0">
                    <div class="d-flex align-items-center gap-2">
                      <a href="{{ route('employees.show', $employee) }}" class="btn btn-sm btn-info">Voir</a>
                      <a href="{{ route('employees.edit', $employee) }}" class="btn btn-sm btn-warning">Modifier</a>
                      <form action="{{ route('employees.destroy', $employee) }}" method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet employé ? Cette action est irréversible.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger">Supprimer</button>
                      </form>
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="text-center py-4">
                    <p class="mb-0">Aucun employé trouvé. <a href="{{ route('employees.create') }}">Ajouter un employé</a></p>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        @if($employees->hasPages())
          <div class="mt-4">
            {{ $employees->links() }}
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection

