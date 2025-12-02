@extends('layouts.app')

@section('title', $employee->full_name)

@section('content')
<div class="row">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h5 class="card-title fw-semibold mb-0">{{ $employee->full_name }}</h5>
          <div class="d-flex gap-2">
            <a href="{{ route('employees.edit', $employee) }}" class="btn btn-warning">
              <i class="ti ti-edit me-2"></i>Modifier
            </a>
            <a href="{{ route('employees.index') }}" class="btn btn-secondary">Retour</a>
          </div>
        </div>

        <div class="row mb-4">
          <div class="col-md-6">
            <h6 class="fw-semibold mb-2">Statut</h6>
            @if($employee->is_active)
              <span class="badge bg-success rounded-3 fw-semibold fs-4">Actif</span>
            @else
              <span class="badge bg-secondary rounded-3 fw-semibold fs-4">Inactif</span>
            @endif
          </div>
          <div class="col-md-6">
            <h6 class="fw-semibold mb-2">Poste</h6>
            @if($employee->position)
              <span class="badge bg-info rounded-3 fw-semibold fs-4">{{ $employee->position }}</span>
            @else
              <span class="text-muted">Non défini</span>
            @endif
          </div>
        </div>

        <div class="row mb-4">
          <div class="col-md-6">
            <h6 class="fw-semibold mb-2">Contact</h6>
            @if($employee->email)
              <p class="mb-1"><strong>Email:</strong> {{ $employee->email }}</p>
            @endif
            @if($employee->phone)
              <p class="mb-0"><strong>Téléphone:</strong> {{ $employee->phone }}</p>
            @endif
          </div>
          <div class="col-md-6">
            <h6 class="fw-semibold mb-2">Informations</h6>
            @if($employee->employee_number)
              <p class="mb-1"><strong>Numéro:</strong> {{ $employee->employee_number }}</p>
            @endif
            @if($employee->hourly_rate)
              <p class="mb-0"><strong>Taux horaire:</strong> {{ number_format($employee->hourly_rate, 2, ',', ' ') }} FCFA/h</p>
            @endif
          </div>
        </div>

        @if($employee->address || $employee->city)
          <div class="mb-4">
            <h6 class="fw-semibold mb-2">Adresse</h6>
            <p class="mb-0">
              @if($employee->address) {{ $employee->address }}<br> @endif
              @if($employee->city) {{ $employee->city }} @endif
              @if($employee->country) {{ $employee->country }} @endif
            </p>
          </div>
        @endif

        @if($employee->notes)
          <div class="mb-4">
            <h6 class="fw-semibold mb-2">Notes</h6>
            <p class="mb-0">{{ $employee->notes }}</p>
          </div>
        @endif

        <hr class="my-4">

        <div class="row mb-4">
          <div class="col-md-3">
            <div class="card bg-light-primary">
              <div class="card-body text-center">
                <h3 class="mb-0">{{ $totalProjects }}</h3>
                <p class="mb-0 text-muted">Projets</p>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card bg-light-success">
              <div class="card-body text-center">
                <h3 class="mb-0">{{ $activeProjects }}</h3>
                <p class="mb-0 text-muted">Projets actifs</p>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card bg-light-info">
              <div class="card-body text-center">
                <h3 class="mb-0">{{ number_format($totalHours, 1) }}</h3>
                <p class="mb-0 text-muted">Heures totales</p>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card bg-light-warning">
              <div class="card-body text-center">
                <h3 class="mb-0">{{ number_format($totalOvertime, 1) }}</h3>
                <p class="mb-0 text-muted">Heures sup.</p>
              </div>
            </div>
          </div>
        </div>

        <h6 class="fw-semibold mb-3">Projets affectés</h6>
        @if($employee->projects->count() > 0)
          <div class="table-responsive">
            <table class="table text-nowrap mb-0 align-middle">
              <thead class="text-dark fs-4">
                <tr>
                  <th class="border-bottom-0">
                    <h6 class="fw-semibold mb-0">Projet</h6>
                  </th>
                  <th class="border-bottom-0">
                    <h6 class="fw-semibold mb-0">Date d'affectation</h6>
                  </th>
                  <th class="border-bottom-0">
                    <h6 class="fw-semibold mb-0">Statut</h6>
                  </th>
                </tr>
              </thead>
              <tbody>
                @foreach($employee->projects as $project)
                  <tr>
                    <td class="border-bottom-0">
                      <a href="{{ route('projects.show', $project) }}" class="text-primary">
                        {{ $project->name }}
                      </a>
                    </td>
                    <td class="border-bottom-0">
                      @if($project->pivot->assigned_date)
                        {{ \Carbon\Carbon::parse($project->pivot->assigned_date)->format('d/m/Y') }}
                      @else
                        <span class="text-muted">-</span>
                      @endif
                    </td>
                    <td class="border-bottom-0">
                      @if($project->pivot->is_active)
                        <span class="badge bg-success">Actif</span>
                      @else
                        <span class="badge bg-secondary">Inactif</span>
                      @endif
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @else
          <p class="text-muted">Cet employé n'est affecté à aucun projet.</p>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection

