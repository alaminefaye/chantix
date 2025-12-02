@extends('layouts.app')

@section('title', $expense->title)

@section('content')
<div class="row">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h5 class="card-title fw-semibold mb-0">{{ $expense->title }}</h5>
          <div class="d-flex gap-2">
            <a href="{{ route('expenses.edit', ['project' => $project, 'expense' => $expense]) }}" class="btn btn-warning">
              <i class="ti ti-edit me-2"></i>Modifier
            </a>
            <a href="{{ route('expenses.index', $project) }}" class="btn btn-secondary">Retour</a>
          </div>
        </div>

        <div class="row mb-4">
          <div class="col-md-6">
            <h6 class="fw-semibold mb-2">Type</h6>
            @php
              $typeColors = [
                'materiaux' => 'info',
                'transport' => 'primary',
                'main_oeuvre' => 'success',
                'location' => 'warning',
                'autres' => 'secondary',
              ];
            @endphp
            <span class="badge bg-{{ $typeColors[$expense->type] ?? 'secondary' }} rounded-3 fw-semibold fs-4">
              {{ $expense->type_label }}
            </span>
          </div>
          <div class="col-md-6">
            <h6 class="fw-semibold mb-2">Montant</h6>
            <p class="fs-4 fw-semibold mb-0">{{ number_format($expense->amount, 2, ',', ' ') }} €</p>
          </div>
        </div>

        <div class="row mb-4">
          <div class="col-md-6">
            <h6 class="fw-semibold mb-2">Date de la dépense</h6>
            <p class="mb-0">{{ $expense->expense_date->format('d/m/Y') }}</p>
          </div>
          <div class="col-md-6">
            <h6 class="fw-semibold mb-2">Statut</h6>
            @if($expense->is_paid)
              <span class="badge bg-success rounded-3 fw-semibold fs-4">Payé</span>
              @if($expense->paid_date)
                <p class="mb-0 text-muted mt-1">Payé le: {{ $expense->paid_date->format('d/m/Y') }}</p>
              @endif
            @else
              <span class="badge bg-danger rounded-3 fw-semibold fs-4">Non payé</span>
            @endif
          </div>
        </div>

        @if($expense->description)
          <div class="mb-4">
            <h6 class="fw-semibold mb-2">Description</h6>
            <p class="mb-0">{{ $expense->description }}</p>
          </div>
        @endif

        @if($expense->material)
          <div class="mb-4">
            <h6 class="fw-semibold mb-2">Matériau</h6>
            <p class="mb-0">{{ $expense->material->name }}</p>
          </div>
        @endif

        @if($expense->employee)
          <div class="mb-4">
            <h6 class="fw-semibold mb-2">Employé</h6>
            <p class="mb-0">{{ $expense->employee->full_name }}</p>
          </div>
        @endif

        <div class="row mb-4">
          <div class="col-md-6">
            <h6 class="fw-semibold mb-2">Fournisseur</h6>
            <p class="mb-0">{{ $expense->supplier ?? '-' }}</p>
          </div>
          <div class="col-md-6">
            <h6 class="fw-semibold mb-2">Numéro de facture</h6>
            <p class="mb-0">{{ $expense->invoice_number ?? '-' }}</p>
          </div>
        </div>

        @if($expense->invoice_date)
          <div class="mb-4">
            <h6 class="fw-semibold mb-2">Date de facture</h6>
            <p class="mb-0">{{ $expense->invoice_date->format('d/m/Y') }}</p>
          </div>
        @endif

        @if($expense->invoice_file)
          <div class="mb-4">
            <h6 class="fw-semibold mb-2">Facture</h6>
            <a href="{{ asset('storage/' . $expense->invoice_file) }}" target="_blank" class="btn btn-primary">
              <i class="ti ti-file me-2"></i>Voir la facture
            </a>
          </div>
        @endif

        @if($expense->notes)
          <div class="mb-4">
            <h6 class="fw-semibold mb-2">Notes</h6>
            <p class="mb-0">{{ $expense->notes }}</p>
          </div>
        @endif

        <div class="mb-4">
          <h6 class="fw-semibold mb-2">Informations</h6>
          <p class="mb-1"><strong>Créé par:</strong> {{ $expense->creator->name ?? 'N/A' }}</p>
          <p class="mb-0"><strong>Créé le:</strong> {{ $expense->created_at->format('d/m/Y à H:i') }}</p>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

