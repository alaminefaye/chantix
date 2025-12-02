@extends('layouts.app')

@section('title', 'Ajouter un matériau')

@section('content')
<div class="row">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h5 class="card-title fw-semibold mb-0">Ajouter un matériau</h5>
          <a href="{{ route('materials.index') }}" class="btn btn-secondary">Retour</a>
        </div>

        @if ($errors->any())
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Erreur !</strong> Veuillez corriger les erreurs suivantes :
            <ul class="mb-0 mt-2">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        @endif

        @if(session('error'))
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        @endif

        <form action="{{ route('materials.store') }}" method="POST" id="materialForm" novalidate>
          @csrf

          <div class="mb-3">
            <label for="name" class="form-label">Nom du matériau <span class="text-danger">*</span></label>
            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
            @error('name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description') }}</textarea>
            @error('description')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="category" class="form-label">Catégorie</label>
              <select class="form-select @error('category') is-invalid @enderror" id="category" name="category">
                <option value="">Sélectionner une catégorie</option>
                <option value="ciment" {{ old('category') == 'ciment' ? 'selected' : '' }}>Ciment</option>
                <option value="acier" {{ old('category') == 'acier' ? 'selected' : '' }}>Acier</option>
                <option value="bois" {{ old('category') == 'bois' ? 'selected' : '' }}>Bois</option>
                <option value="electricite" {{ old('category') == 'electricite' ? 'selected' : '' }}>Électricité</option>
                <option value="plomberie" {{ old('category') == 'plomberie' ? 'selected' : '' }}>Plomberie</option>
                <option value="peinture" {{ old('category') == 'peinture' ? 'selected' : '' }}>Peinture</option>
                <option value="autres" {{ old('category') == 'autres' ? 'selected' : '' }}>Autres</option>
              </select>
              @error('category')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6 mb-3">
              <label for="unit" class="form-label">Unité <span class="text-danger">*</span></label>
              <select class="form-select @error('unit') is-invalid @enderror" id="unit" name="unit" required>
                <option value="">Sélectionner une unité</option>
                <option value="kg" {{ old('unit') == 'kg' ? 'selected' : '' }}>kg (Kilogramme)</option>
                <option value="g" {{ old('unit') == 'g' ? 'selected' : '' }}>g (Gramme)</option>
                <option value="m" {{ old('unit') == 'm' ? 'selected' : '' }}>m (Mètre)</option>
                <option value="m²" {{ old('unit') == 'm²' ? 'selected' : '' }}>m² (Mètre carré)</option>
                <option value="m³" {{ old('unit') == 'm³' ? 'selected' : '' }}>m³ (Mètre cube)</option>
                <option value="cm" {{ old('unit') == 'cm' ? 'selected' : '' }}>cm (Centimètre)</option>
                <option value="L" {{ old('unit') == 'L' ? 'selected' : '' }}>L (Litre)</option>
                <option value="mL" {{ old('unit') == 'mL' ? 'selected' : '' }}>mL (Millilitre)</option>
                <option value="pièce" {{ old('unit') == 'pièce' ? 'selected' : '' }}>Pièce</option>
                <option value="unité" {{ old('unit', 'unité') == 'unité' ? 'selected' : '' }}>Unité</option>
                <option value="paquet" {{ old('unit') == 'paquet' ? 'selected' : '' }}>Paquet</option>
                <option value="rouleau" {{ old('unit') == 'rouleau' ? 'selected' : '' }}>Rouleau</option>
                <option value="boîte" {{ old('unit') == 'boîte' ? 'selected' : '' }}>Boîte</option>
                <option value="sac" {{ old('unit') == 'sac' ? 'selected' : '' }}>Sac</option>
                <option value="palette" {{ old('unit') == 'palette' ? 'selected' : '' }}>Palette</option>
              </select>
              @error('unit')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="unit_price" class="form-label">Prix unitaire (€)</label>
              <input type="number" step="0.01" min="0" class="form-control @error('unit_price') is-invalid @enderror" id="unit_price" name="unit_price" value="{{ old('unit_price', 0) }}">
              @error('unit_price')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6 mb-3">
              <label for="supplier" class="form-label">Fournisseur</label>
              <input type="text" class="form-control @error('supplier') is-invalid @enderror" id="supplier" name="supplier" value="{{ old('supplier') }}">
              @error('supplier')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="reference" class="form-label">Référence fournisseur</label>
              <input type="text" class="form-control @error('reference') is-invalid @enderror" id="reference" name="reference" value="{{ old('reference') }}">
              @error('reference')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6 mb-3">
              <label for="stock_quantity" class="form-label">Stock initial</label>
              <input type="number" step="0.01" min="0" class="form-control @error('stock_quantity') is-invalid @enderror" id="stock_quantity" name="stock_quantity" value="{{ old('stock_quantity', 0) }}">
              @error('stock_quantity')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div class="mb-3">
            <label for="min_stock" class="form-label">Seuil minimum (alerte)</label>
            <input type="number" step="0.01" min="0" class="form-control @error('min_stock') is-invalid @enderror" id="min_stock" name="min_stock" value="{{ old('min_stock', 0) }}">
            <small class="text-muted">Une alerte sera déclenchée lorsque le stock atteindra ce seuil</small>
            @error('min_stock')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
              <label class="form-check-label" for="is_active">
                Matériau actif
              </label>
            </div>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary" id="submitBtn">
              <span class="spinner-border spinner-border-sm d-none me-2" id="spinner" role="status" aria-hidden="true"></span>
              <span id="btnText">Créer le matériau</span>
            </button>
            <a href="{{ route('materials.index') }}" class="btn btn-secondary">Annuler</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
(function() {
  'use strict';
  
  const form = document.getElementById('materialForm');
  const submitBtn = document.getElementById('submitBtn');
  const spinner = document.getElementById('spinner');
  const btnText = document.getElementById('btnText');
  
  if (form && submitBtn) {
    form.addEventListener('submit', function(e) {
      // Ne pas empêcher la soumission, juste afficher le feedback
      if (submitBtn && !submitBtn.disabled) {
        submitBtn.disabled = true;
        if (spinner) spinner.classList.remove('d-none');
        if (btnText) btnText.textContent = 'Création en cours...';
      }
    });
  }
  
  // Scroll vers les erreurs si présentes
  @if ($errors->any())
    setTimeout(function() {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }, 100);
  @endif
})();
</script>
@endpush
@endsection
