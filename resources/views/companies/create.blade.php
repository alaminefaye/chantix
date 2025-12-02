@extends('layouts.app')

@section('title', 'Créer une entreprise')

@section('content')
<div class="row">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <h5 class="card-title fw-semibold mb-4">Créer une nouvelle entreprise</h5>

        @if ($errors->any())
          <div class="alert alert-danger">
            <ul class="mb-0">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <form action="{{ route('companies.store') }}" method="POST">
          @csrf
          <div class="mb-3">
            <label for="name" class="form-label">Nom de l'entreprise <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required>
          </div>
          <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}">
          </div>
          <div class="mb-3">
            <label for="phone" class="form-label">Téléphone</label>
            <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone') }}">
          </div>
          <div class="mb-3">
            <label for="address" class="form-label">Adresse</label>
            <textarea class="form-control" id="address" name="address" rows="2">{{ old('address') }}</textarea>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="city" class="form-label">Ville</label>
              <input type="text" class="form-control" id="city" name="city" value="{{ old('city') }}">
            </div>
            <div class="col-md-6 mb-3">
              <label for="country" class="form-label">Pays</label>
              <input type="text" class="form-control" id="country" name="country" value="{{ old('country') }}">
            </div>
          </div>
          <div class="mb-3">
            <label for="siret" class="form-label">SIRET</label>
            <input type="text" class="form-control" id="siret" name="siret" value="{{ old('siret') }}">
          </div>
          <div class="mb-4">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control" id="description" name="description" rows="3">{{ old('description') }}</textarea>
          </div>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Créer l'entreprise</button>
            <a href="{{ route('companies.index') }}" class="btn btn-secondary">Annuler</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

