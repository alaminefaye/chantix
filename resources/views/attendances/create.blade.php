@extends('layouts.app')

@section('title', 'Nouveau pointage - ' . $project->name)

@section('content')
<div class="row">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h5 class="card-title fw-semibold mb-0">Nouveau pointage - {{ $project->name }}</h5>
          <a href="{{ route('attendances.index', $project) }}?date={{ $date }}" class="btn btn-secondary">Retour</a>
        </div>

        <ul class="nav nav-tabs mb-4" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="checkin-tab" data-bs-toggle="tab" data-bs-target="#checkin" type="button" role="tab">
              Check-in
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="absence-tab" data-bs-toggle="tab" data-bs-target="#absence" type="button" role="tab">
              Absence
            </button>
          </li>
        </ul>

        <div class="tab-content">
          <!-- Tab Check-in -->
          <div class="tab-pane fade show active" id="checkin" role="tabpanel">
            <form action="{{ route('attendances.check-in', $project) }}" method="POST" enctype="multipart/form-data">
              @csrf

              <div class="mb-3">
                <label for="employee_id" class="form-label">Employé <span class="text-danger">*</span></label>
                <select class="form-select @error('employee_id') is-invalid @enderror" id="employee_id" name="employee_id" required>
                  <option value="">Sélectionner un employé</option>
                  @foreach($employees as $emp)
                    <option value="{{ $emp->id }}" {{ old('employee_id') == $emp->id ? 'selected' : '' }}>
                      {{ $emp->full_name }} @if($emp->position) - {{ $emp->position }} @endif
                    </option>
                  @endforeach
                </select>
                @error('employee_id')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="date" class="form-label">Date <span class="text-danger">*</span></label>
                  <input type="date" class="form-control @error('date') is-invalid @enderror" id="date" name="date" value="{{ old('date', $date) }}" required>
                  @error('date')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <div class="col-md-6 mb-3">
                  <label for="check_in" class="form-label">Heure d'arrivée <span class="text-danger">*</span></label>
                  <input type="time" class="form-control @error('check_in') is-invalid @enderror" id="check_in" name="check_in" value="{{ old('check_in', now()->format('H:i')) }}" required>
                  @error('check_in')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
              </div>

              <div class="mb-3">
                <label for="check_in_location" class="form-label">Localisation (GPS)</label>
                <input type="text" class="form-control @error('check_in_location') is-invalid @enderror" id="check_in_location" name="check_in_location" value="{{ old('check_in_location') }}" placeholder="Ex: 48.8566, 2.3522">
                @error('check_in_location')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="mb-3">
                <label for="check_in_photo" class="form-label">Photo de pointage (optionnel)</label>
                <input type="file" class="form-control @error('check_in_photo') is-invalid @enderror" id="check_in_photo" name="check_in_photo" accept="image/*" capture="environment">
                <small class="text-muted">Prendre une photo ou sélectionner un fichier (max 2MB)</small>
                @error('check_in_photo')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div id="checkInPhotoPreview" class="mt-2"></div>
              </div>

              <div class="mb-3">
                <label for="notes" class="form-label">Notes</label>
                <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
                @error('notes')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Enregistrer le check-in</button>
                <a href="{{ route('attendances.index', $project) }}?date={{ $date }}" class="btn btn-secondary">Annuler</a>
              </div>
            </form>
          </div>

          <!-- Tab Absence -->
          <div class="tab-pane fade" id="absence" role="tabpanel">
            <form action="{{ route('attendances.absence', $project) }}" method="POST">
              @csrf

              <div class="mb-3">
                <label for="employee_id_absence" class="form-label">Employé <span class="text-danger">*</span></label>
                <select class="form-select @error('employee_id') is-invalid @enderror" id="employee_id_absence" name="employee_id" required>
                  <option value="">Sélectionner un employé</option>
                  @foreach($employees as $emp)
                    <option value="{{ $emp->id }}" {{ old('employee_id') == $emp->id ? 'selected' : '' }}>
                      {{ $emp->full_name }} @if($emp->position) - {{ $emp->position }} @endif
                    </option>
                  @endforeach
                </select>
                @error('employee_id')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="mb-3">
                <label for="date_absence" class="form-label">Date <span class="text-danger">*</span></label>
                <input type="date" class="form-control @error('date') is-invalid @enderror" id="date_absence" name="date" value="{{ old('date', $date) }}" required>
                @error('date')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="mb-3">
                <label for="absence_reason" class="form-label">Raison de l'absence <span class="text-danger">*</span></label>
                <select class="form-select @error('absence_reason') is-invalid @enderror" id="absence_reason" name="absence_reason" required>
                  <option value="">Sélectionner une raison</option>
                  <option value="Maladie">Maladie</option>
                  <option value="Congé">Congé</option>
                  <option value="Accident">Accident</option>
                  <option value="Autre">Autre</option>
                </select>
                @error('absence_reason')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="mb-3">
                <label for="notes_absence" class="form-label">Notes</label>
                <textarea class="form-control @error('notes') is-invalid @enderror" id="notes_absence" name="notes" rows="3">{{ old('notes') }}</textarea>
                @error('notes')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="d-flex gap-2">
                <button type="submit" class="btn btn-danger">Enregistrer l'absence</button>
                <a href="{{ route('attendances.index', $project) }}?date={{ $date }}" class="btn btn-secondary">Annuler</a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.getElementById('check_in_photo')?.addEventListener('change', function(e) {
    const preview = document.getElementById('checkInPhotoPreview');
    if (e.target.files && e.target.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = '<img src="' + e.target.result + '" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">';
        };
        reader.readAsDataURL(e.target.files[0]);
    } else {
        preview.innerHTML = '';
    }
});
</script>
@endsection

