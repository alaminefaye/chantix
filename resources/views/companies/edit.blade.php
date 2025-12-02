@extends('layouts.app')

@section('title', 'Modifier l\'entreprise - ' . $company->name)

@section('content')
<div class="row">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h5 class="card-title fw-semibold mb-0">Modifier l'entreprise - {{ $company->name }}</h5>
          <a href="{{ route('companies.show', $company) }}" class="btn btn-secondary">Retour</a>
        </div>

        @if(session('success'))
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        @endif

        <form action="{{ route('companies.update', $company) }}" method="POST" enctype="multipart/form-data">
          @csrf
          @method('PUT')

          <div class="mb-3 text-center">
            @if($company->logo)
              <img src="{{ Storage::url($company->logo) }}" alt="Logo" class="mb-3" style="max-height: 120px;">
            @else
              <div class="bg-light d-inline-flex align-items-center justify-content-center mb-3" style="width: 120px; height: 120px; border-radius: 8px;">
                <i class="ti ti-building" style="font-size: 48px; color: #ccc;"></i>
              </div>
            @endif
            <div>
              <label for="logo" class="btn btn-sm btn-outline-primary">
                <i class="ti ti-upload me-1"></i>{{ $company->logo ? 'Changer le logo' : 'Ajouter un logo' }}
              </label>
              <input type="file" class="d-none" id="logo" name="logo" accept="image/*">
              <small class="d-block text-muted mt-2">Format: JPG, PNG, GIF (max 2MB)</small>
            </div>
          </div>

          <div class="mb-3">
            <label for="name" class="form-label">Nom de l'entreprise <span class="text-danger">*</span></label>
            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $company->name) }}" required>
            @error('name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $company->email) }}">
            @error('email')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label for="phone" class="form-label">Téléphone</label>
            <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $company->phone) }}">
            @error('phone')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label for="address" class="form-label">Adresse</label>
            <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="2">{{ old('address', $company->address) }}</textarea>
            @error('address')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <label for="city" class="form-label">Ville</label>
              <input type="text" class="form-control @error('city') is-invalid @enderror" id="city" name="city" value="{{ old('city', $company->city) }}">
              @error('city')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            <div class="col-md-6">
              <label for="country" class="form-label">Pays</label>
              <input type="text" class="form-control @error('country') is-invalid @enderror" id="country" name="country" value="{{ old('country', $company->country) }}">
              @error('country')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div class="mb-3">
            <label for="siret" class="form-label">SIRET</label>
            <input type="text" class="form-control @error('siret') is-invalid @enderror" id="siret" name="siret" value="{{ old('siret', $company->siret) }}">
            @error('siret')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $company->description) }}</textarea>
            @error('description')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="{{ route('companies.show', $company) }}" class="btn btn-secondary">Annuler</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
document.getElementById('logo').addEventListener('change', function(e) {
    if (e.target.files && e.target.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = document.querySelector('img[alt="Logo"]') || document.querySelector('.bg-light');
            if (img.tagName === 'IMG') {
                img.src = e.target.result;
            } else {
                img.outerHTML = '<img src="' + e.target.result + '" alt="Logo" class="mb-3" style="max-height: 120px;">';
            }
        };
        reader.readAsDataURL(e.target.files[0]);
    }
});
</script>
@endsection

