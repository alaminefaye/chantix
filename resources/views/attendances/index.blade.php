@extends('layouts.app')

@section('title', 'Pointage - ' . $project->name)

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <div>
            <h5 class="card-title fw-semibold mb-0">Pointage - {{ $project->name }}</h5>
            <p class="text-muted mb-0">Date: {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</p>
          </div>
          <div class="d-flex gap-2">
            @if(auth()->user()->canManageProject($project, 'edit') || auth()->user()->hasRoleInCompany('admin') || auth()->user()->hasPermission('attendances.manage'))
              <a href="{{ route('attendances.create', $project) }}?date={{ $date }}" class="btn btn-primary">
                <i class="ti ti-plus me-2"></i>Nouveau pointage
              </a>
            @endif
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

        <!-- Filtres -->
        <form method="GET" action="{{ route('attendances.index', $project) }}" class="mb-4">
          <div class="row">
            <div class="col-md-4 mb-3">
              <label for="date" class="form-label">Date</label>
              <input type="date" class="form-control" id="date" name="date" value="{{ $date }}">
            </div>
            <div class="col-md-4 mb-3">
              <label for="employee_id" class="form-label">Employé</label>
              <select class="form-select" id="employee_id" name="employee_id">
                <option value="">Tous les employés</option>
                @foreach($employees as $emp)
                  <option value="{{ $emp->id }}" {{ $employeeId == $emp->id ? 'selected' : '' }}>
                    {{ $emp->full_name }}
                  </option>
                @endforeach
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
                  <h6 class="fw-semibold mb-0">Employé</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Check-in</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Check-out</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Heures</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Heures sup.</h6>
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
              @forelse($attendances as $attendance)
                <tr>
                  <td class="border-bottom-0">
                    <h6 class="fw-semibold mb-0">{{ $attendance->employee->full_name }}</h6>
                    @if($attendance->employee->position)
                      <span class="badge bg-info" style="font-size: 0.7rem;">{{ $attendance->employee->position }}</span>
                    @endif
                  </td>
                  <td class="border-bottom-0">
                    @if($attendance->check_in)
                      <p class="mb-0 fw-normal">{{ $attendance->check_in }}</p>
                      @if($attendance->check_in_location)
                        <small class="text-muted d-block">{{ $attendance->check_in_location }}</small>
                      @endif
                      @if($attendance->check_in_photo)
                        <a href="{{ Storage::url($attendance->check_in_photo) }}" target="_blank" class="btn btn-sm btn-outline-info mt-1">
                          <i class="ti ti-photo me-1"></i>Voir photo
                        </a>
                      @endif
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>
                  <td class="border-bottom-0">
                    @if($attendance->check_out)
                      <p class="mb-0 fw-normal">{{ $attendance->check_out }}</p>
                      @if($attendance->check_out_location)
                        <small class="text-muted d-block">{{ $attendance->check_out_location }}</small>
                      @endif
                      @if($attendance->check_out_photo)
                        <a href="{{ Storage::url($attendance->check_out_photo) }}" target="_blank" class="btn btn-sm btn-outline-info mt-1">
                          <i class="ti ti-photo me-1"></i>Voir photo
                        </a>
                      @endif
                    @else
                      @if($attendance->check_in)
                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#checkOutModal{{ $attendance->id }}">
                          Check-out
                        </button>
                      @else
                        <span class="text-muted">-</span>
                      @endif
                    @endif
                  </td>
                  <td class="border-bottom-0">
                    @if($attendance->hours_worked)
                      <h6 class="fw-semibold mb-0">{{ number_format($attendance->hours_worked, 2, ',', ' ') }}h</h6>
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>
                  <td class="border-bottom-0">
                    @if($attendance->overtime_hours > 0)
                      <span class="badge bg-warning">{{ number_format($attendance->overtime_hours, 2, ',', ' ') }}h</span>
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>
                  <td class="border-bottom-0">
                    @if($attendance->is_present)
                      <span class="badge bg-success">Présent</span>
                    @else
                      <span class="badge bg-danger">Absent</span>
                      @if($attendance->absence_reason)
                        <br><small class="text-muted">{{ $attendance->absence_reason }}</small>
                      @endif
                    @endif
                  </td>
                  <td class="border-bottom-0">
                    <div class="d-flex align-items-center gap-2">
                      @if(auth()->user()->canManageProject($project, 'edit') || auth()->user()->hasRoleInCompany('admin') || auth()->user()->hasPermission('attendances.manage'))
                        <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal{{ $attendance->id }}">
                          <i class="ti ti-edit"></i>
                        </button>
                        <form action="{{ route('attendances.destroy', ['project' => $project, 'attendance' => $attendance]) }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce pointage ?');">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="btn btn-sm btn-danger">
                            <i class="ti ti-trash"></i>
                          </button>
                        </form>
                      @else
                        <span class="text-muted">Aucune action disponible</span>
                      @endif
                    </div>
                  </td>
                </tr>

                <!-- Modal Check-out -->
                @if($attendance->check_in && !$attendance->check_out)
                  <div class="modal fade" id="checkOutModal{{ $attendance->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h5 class="modal-title">Check-out - {{ $attendance->employee->full_name }}</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form action="{{ route('attendances.check-out', ['project' => $project, 'attendance' => $attendance]) }}" method="POST" enctype="multipart/form-data">
                          @csrf
                          <div class="modal-body">
                            <div class="mb-3">
                              <label for="check_out{{ $attendance->id }}" class="form-label">Heure de départ <span class="text-danger">*</span></label>
                              <input type="time" class="form-control" id="check_out{{ $attendance->id }}" name="check_out" value="{{ now()->format('H:i') }}" required>
                            </div>
                            <div class="mb-3">
                              <label for="check_out_location{{ $attendance->id }}" class="form-label">Localisation</label>
                              <input type="text" class="form-control" id="check_out_location{{ $attendance->id }}" name="check_out_location" placeholder="GPS ou adresse">
                            </div>
                            <div class="mb-3">
                              <label for="check_out_photo{{ $attendance->id }}" class="form-label">Photo de pointage (optionnel)</label>
                              <input type="file" class="form-control" id="check_out_photo{{ $attendance->id }}" name="check_out_photo" accept="image/*" capture="environment">
                              <small class="text-muted">Prendre une photo ou sélectionner un fichier (max 2MB)</small>
                              <div id="checkOutPhotoPreview{{ $attendance->id }}" class="mt-2"></div>
                            </div>
                            <div class="mb-3">
                              <label for="notes{{ $attendance->id }}" class="form-label">Notes</label>
                              <textarea class="form-control" id="notes{{ $attendance->id }}" name="notes" rows="3"></textarea>
                            </div>
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>
                @endif

                <!-- Modal Edit -->
                <div class="modal fade" id="editModal{{ $attendance->id }}" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title">Modifier le pointage</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>
                      <form action="{{ route('attendances.update', ['project' => $project, 'attendance' => $attendance]) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-body">
                          <div class="mb-3">
                            <label for="check_in_edit{{ $attendance->id }}" class="form-label">Check-in</label>
                            <input type="time" class="form-control" id="check_in_edit{{ $attendance->id }}" name="check_in" value="{{ $attendance->check_in }}">
                          </div>
                          <div class="mb-3">
                            <label for="check_out_edit{{ $attendance->id }}" class="form-label">Check-out</label>
                            <input type="time" class="form-control" id="check_out_edit{{ $attendance->id }}" name="check_out" value="{{ $attendance->check_out }}">
                          </div>
                          <div class="mb-3">
                            <label for="notes_edit{{ $attendance->id }}" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes_edit{{ $attendance->id }}" name="notes" rows="3">{{ $attendance->notes }}</textarea>
                          </div>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                          <button type="submit" class="btn btn-primary">Mettre à jour</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
              @empty
                <tr>
                  <td colspan="7" class="text-center py-4">
                    <p class="mb-0">Aucun pointage trouvé pour cette date.</p>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// Preview des photos de check-out
document.querySelectorAll('[id^="check_out_photo"]').forEach(function(input) {
    input.addEventListener('change', function(e) {
        const attendanceId = this.id.replace('check_out_photo', '');
        const preview = document.getElementById('checkOutPhotoPreview' + attendanceId);
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
});
</script>
@endsection

