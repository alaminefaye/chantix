@extends('layouts.app')

@section('title', 'Dépenses - ' . $project->name)

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h5 class="card-title fw-semibold mb-0">Dépenses - {{ $project->name }}</h5>
          <div class="d-flex gap-2">
            <a href="{{ route('expenses.create', $project) }}" class="btn btn-primary">
              <i class="ti ti-plus me-2"></i>Nouvelle dépense
            </a>
            <a href="{{ route('projects.show', $project) }}" class="btn btn-secondary">Retour</a>
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

        <!-- Statistiques -->
        <div class="row mb-4">
          <div class="col-md-3">
            <div class="card bg-light-primary">
              <div class="card-body text-center">
                <h3 class="mb-0">{{ number_format($totalExpenses, 2, ',', ' ') }} FCFA</h3>
                <p class="mb-0 text-muted">Total dépenses</p>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card bg-light-success">
              <div class="card-body text-center">
                <h3 class="mb-0">{{ number_format($paidExpenses, 2, ',', ' ') }} FCFA</h3>
                <p class="mb-0 text-muted">Payé</p>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card bg-light-danger">
              <div class="card-body text-center">
                <h3 class="mb-0">{{ number_format($unpaidExpenses, 2, ',', ' ') }} FCFA</h3>
                <p class="mb-0 text-muted">Non payé</p>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card bg-light-info">
              <div class="card-body text-center">
                <h3 class="mb-0">{{ number_format($project->budget - $totalExpenses, 2, ',', ' ') }} FCFA</h3>
                <p class="mb-0 text-muted">Budget restant</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Filtres -->
        <form method="GET" action="{{ route('expenses.index', $project) }}" class="mb-4">
          <div class="row">
            <div class="col-md-4 mb-3">
              <label for="type" class="form-label">Type de dépense</label>
              <select class="form-select" id="type" name="type">
                <option value="">Tous les types</option>
                <option value="materiaux" {{ $type == 'materiaux' ? 'selected' : '' }}>Matériaux</option>
                <option value="transport" {{ $type == 'transport' ? 'selected' : '' }}>Transport</option>
                <option value="main_oeuvre" {{ $type == 'main_oeuvre' ? 'selected' : '' }}>Main-d'œuvre</option>
                <option value="location" {{ $type == 'location' ? 'selected' : '' }}>Location machines</option>
                <option value="autres" {{ $type == 'autres' ? 'selected' : '' }}>Autres</option>
              </select>
            </div>
            <div class="col-md-4 mb-3 d-flex align-items-end">
              <button type="submit" class="btn btn-primary w-100">Filtrer</button>
            </div>
          </div>
        </form>

        <div class="table-responsive">
          <table class="table text-nowrap mb-0 align-middle">
            <thead class="text-dark fs-4">
              <tr>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Date</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Titre</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Type</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Montant</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Fournisseur</h6>
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
              @forelse($expenses as $expense)
                <tr>
                  <td class="border-bottom-0">
                    <p class="mb-0 fw-normal">{{ $expense->expense_date->format('d/m/Y') }}</p>
                  </td>
                  <td class="border-bottom-0">
                    <h6 class="fw-semibold mb-0">{{ $expense->title }}</h6>
                    @if($expense->description)
                      <p class="mb-0 fw-normal text-muted" style="font-size: 0.85rem;">{{ Str::limit($expense->description, 50) }}</p>
                    @endif
                  </td>
                  <td class="border-bottom-0">
                    @php
                      $typeColors = [
                        'materiaux' => 'info',
                        'transport' => 'primary',
                        'main_oeuvre' => 'success',
                        'location' => 'warning',
                        'autres' => 'secondary',
                      ];
                    @endphp
                    <span class="badge bg-{{ $typeColors[$expense->type] ?? 'secondary' }} rounded-3 fw-semibold">
                      {{ $expense->type_label }}
                    </span>
                  </td>
                  <td class="border-bottom-0">
                    <h6 class="fw-semibold mb-0 fs-4">{{ number_format($expense->amount, 2, ',', ' ') }} FCFA</h6>
                  </td>
                  <td class="border-bottom-0">
                    @if($expense->supplier)
                      <p class="mb-0 fw-normal">{{ $expense->supplier }}</p>
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>
                  <td class="border-bottom-0">
                    @if($expense->is_paid)
                      <span class="badge bg-success rounded-3 fw-semibold">Payé</span>
                      @if($expense->paid_date)
                        <br><small class="text-muted">{{ $expense->paid_date->format('d/m/Y') }}</small>
                      @endif
                    @else
                      <span class="badge bg-danger rounded-3 fw-semibold">Non payé</span>
                    @endif
                  </td>
                  <td class="border-bottom-0">
                    <div class="d-flex align-items-center gap-2">
                      <a href="{{ route('expenses.show', ['project' => $project, 'expense' => $expense]) }}" class="btn btn-sm btn-info">Voir</a>
                      <a href="{{ route('expenses.edit', ['project' => $project, 'expense' => $expense]) }}" class="btn btn-sm btn-warning">Modifier</a>
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="text-center py-4">
                    <p class="mb-0">Aucune dépense trouvée. <a href="{{ route('expenses.create', $project) }}">Créer une dépense</a></p>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        @if($expenses->hasPages())
          <div class="mt-4">
            {{ $expenses->links() }}
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection

