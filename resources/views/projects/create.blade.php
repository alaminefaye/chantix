@extends('layouts.app')

@section('title', 'Créer un projet')

@section('content')
<div class="row">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <h5 class="card-title fw-semibold mb-4">Créer un nouveau projet</h5>

        @if ($errors->any())
          <div class="alert alert-danger">
            <ul class="mb-0">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <form action="{{ route('projects.store') }}" method="POST">
          @csrf
          <div class="mb-3">
            <label for="name" class="form-label">Nom du projet <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required>
          </div>
          <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control" id="description" name="description" rows="3">{{ old('description') }}</textarea>
          </div>
          <div class="mb-3">
            <label for="address" class="form-label">Adresse du chantier</label>
            <input type="text" class="form-control" id="address" name="address" value="{{ old('address') }}">
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="latitude" class="form-label">Latitude (GPS)</label>
              <input type="number" step="any" class="form-control" id="latitude" name="latitude" value="{{ old('latitude') }}">
            </div>
            <div class="col-md-6 mb-3">
              <label for="longitude" class="form-label">Longitude (GPS)</label>
              <input type="number" step="any" class="form-control" id="longitude" name="longitude" value="{{ old('longitude') }}">
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="start_date" class="form-label">Date de début</label>
              <input type="date" class="form-control" id="start_date" name="start_date" value="{{ old('start_date') }}">
            </div>
            <div class="col-md-6 mb-3">
              <label for="end_date" class="form-label">Date de fin prévue</label>
              <input type="date" class="form-control" id="end_date" name="end_date" value="{{ old('end_date') }}">
            </div>
          </div>
          <div class="mb-3">
            <label for="budget" class="form-label">Budget (€)</label>
            <input type="number" step="0.01" class="form-control" id="budget" name="budget" value="{{ old('budget') }}" min="0">
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="client_name" class="form-label">Nom du client</label>
              <input type="text" class="form-control" id="client_name" name="client_name" value="{{ old('client_name') }}">
            </div>
            <div class="col-md-6 mb-3">
              <label for="client_contact" class="form-label">Contact client</label>
              <input type="text" class="form-control" id="client_contact" name="client_contact" value="{{ old('client_contact') }}">
            </div>
          </div>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Créer le projet</button>
            <a href="{{ route('projects.index') }}" class="btn btn-secondary">Annuler</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

