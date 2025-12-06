@extends('layouts.app')

@section('title', 'Modifier la dépense - ' . $project->name)

@section('content')
<div class="row">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h5 class="card-title fw-semibold mb-0">Modifier la dépense - {{ $project->name }}</h5>
          <a href="{{ route('expenses.index', $project) }}" class="btn btn-secondary">Retour</a>
        </div>

        <form action="{{ route('expenses.update', ['project' => $project, 'expense' => $expense]) }}" method="POST" enctype="multipart/form-data">
          @csrf
          @method('PUT')

          <div class="mb-3">
            <label for="type" class="form-label">Type de dépense <span class="text-danger">*</span></label>
            <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required onchange="toggleTypeFields()">
              <option value="materiaux" {{ old('type', $expense->type) == 'materiaux' ? 'selected' : '' }}>Matériaux</option>
              <option value="transport" {{ old('type', $expense->type) == 'transport' ? 'selected' : '' }}>Transport</option>
              <option value="main_oeuvre" {{ old('type', $expense->type) == 'main_oeuvre' ? 'selected' : '' }}>Main-d'œuvre</option>
              <option value="location" {{ old('type', $expense->type) == 'location' ? 'selected' : '' }}>Location machines</option>
              <option value="autres" {{ old('type', $expense->type) == 'autres' ? 'selected' : '' }}>Autres</option>
            </select>
            @error('type')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label for="title" class="form-label">Titre <span class="text-danger">*</span></label>
            <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $expense->title) }}" required>
            @error('title')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $expense->description) }}</textarea>
            @error('description')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="amount" class="form-label">Montant (FCFA) <span class="text-danger">*</span></label>
              <input type="number" step="0.01" min="0" class="form-control @error('amount') is-invalid @enderror" id="amount" name="amount" value="{{ old('amount', $expense->amount) }}" required>
              @error('amount')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6 mb-3">
              <label for="expense_date" class="form-label">Date de la dépense <span class="text-danger">*</span></label>
              <input type="date" class="form-control @error('expense_date') is-invalid @enderror" id="expense_date" name="expense_date" value="{{ old('expense_date', $expense->expense_date->format('Y-m-d')) }}" required>
              @error('expense_date')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div id="material_field" style="display: {{ old('type', $expense->type) == 'materiaux' ? 'block' : 'none' }};">
            <div class="mb-3">
              <label for="material_id" class="form-label">Matériau</label>
              <select class="form-select @error('material_id') is-invalid @enderror" id="material_id" name="material_id">
                <option value="">Sélectionner un matériau</option>
                @foreach($materials as $material)
                  <option value="{{ $material->id }}" {{ old('material_id', $expense->material_id) == $material->id ? 'selected' : '' }}>
                    {{ $material->name }}
                  </option>
                @endforeach
              </select>
              @error('material_id')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div id="employee_field" style="display: {{ old('type', $expense->type) == 'main_oeuvre' ? 'block' : 'none' }};">
            <div class="mb-3">
              <label for="employee_id" class="form-label">Employé</label>
              <select class="form-select @error('employee_id') is-invalid @enderror" id="employee_id" name="employee_id">
                <option value="">Sélectionner un employé</option>
                @foreach($employees as $employee)
                  <option value="{{ $employee->id }}" {{ old('employee_id', $expense->employee_id) == $employee->id ? 'selected' : '' }}>
                    {{ $employee->full_name }}
                  </option>
                @endforeach
              </select>
              @error('employee_id')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="supplier" class="form-label">Fournisseur</label>
              <input type="text" class="form-control @error('supplier') is-invalid @enderror" id="supplier" name="supplier" value="{{ old('supplier', $expense->supplier) }}">
              @error('supplier')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6 mb-3">
              <label for="invoice_number" class="form-label">Numéro de facture</label>
              <input type="text" class="form-control @error('invoice_number') is-invalid @enderror" id="invoice_number" name="invoice_number" value="{{ old('invoice_number', $expense->invoice_number) }}">
              @error('invoice_number')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div class="mb-3">
            <label for="invoice_date" class="form-label">Date de facture</label>
            <input type="date" class="form-control @error('invoice_date') is-invalid @enderror" id="invoice_date" name="invoice_date" value="{{ old('invoice_date', $expense->invoice_date?->format('Y-m-d')) }}">
            @error('invoice_date')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label for="invoice_file" class="form-label">Facture (photo/PDF)</label>
            @if($expense->invoice_file)
              <div class="mb-2">
                <a href="{{ asset('storage/' . $expense->invoice_file) }}" target="_blank" class="btn btn-sm btn-info">
                  <i class="ti ti-file me-1"></i>Voir la facture actuelle
                </a>
              </div>
            @endif
            <input type="file" class="form-control @error('invoice_file') is-invalid @enderror" id="invoice_file" name="invoice_file" accept="image/*,.pdf">
            <small class="text-muted">Laisser vide pour conserver la facture actuelle. Formats acceptés: JPG, PNG, PDF (max 10MB)</small>
            @error('invoice_file')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label for="notes" class="form-label">Notes</label>
            <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3">{{ old('notes', $expense->notes) }}</textarea>
            @error('notes')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="is_paid" name="is_paid" value="1" {{ old('is_paid', $expense->is_paid) ? 'checked' : '' }} onchange="togglePaidDate()">
                <label class="form-check-label" for="is_paid">
                  Dépense payée
                </label>
              </div>
            </div>

            <div class="col-md-6 mb-3" id="paid_date_field" style="display: {{ old('is_paid', $expense->is_paid) ? 'block' : 'none' }};">
              <label for="paid_date" class="form-label">Date de paiement</label>
              <input type="date" class="form-control @error('paid_date') is-invalid @enderror" id="paid_date" name="paid_date" value="{{ old('paid_date', $expense->paid_date?->format('Y-m-d')) }}">
              @error('paid_date')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Mettre à jour</button>
            <a href="{{ route('expenses.index', $project) }}" class="btn btn-secondary">Annuler</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
function toggleTypeFields() {
  const type = document.getElementById('type').value;
  const materialField = document.getElementById('material_field');
  const employeeField = document.getElementById('employee_field');
  
  materialField.style.display = (type === 'materiaux') ? 'block' : 'none';
  employeeField.style.display = (type === 'main_oeuvre') ? 'block' : 'none';
}

function togglePaidDate() {
  const isPaid = document.getElementById('is_paid').checked;
  const paidDateField = document.getElementById('paid_date_field');
  paidDateField.style.display = isPaid ? 'block' : 'none';
}
</script>
@endsection

