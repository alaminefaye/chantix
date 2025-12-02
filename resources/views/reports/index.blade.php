@extends('layouts.app')

@section('title', 'Rapports - ' . $project->name)

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h5 class="card-title fw-semibold mb-0">Rapports - {{ $project->name }}</h5>
          <a href="{{ route('projects.show', $project) }}" class="btn btn-secondary">Retour</a>
        </div>

        <!-- Génération de rapports -->
        <div class="row mb-4">
          <div class="col-md-6">
            <div class="card bg-light-primary">
              <div class="card-body">
                <h6 class="fw-semibold mb-3">Rapport Journalier</h6>
                <form action="{{ route('reports.daily', $project) }}" method="GET" class="mb-3">
                  <div class="mb-3">
                    <label for="date" class="form-label">Date</label>
                    <input type="date" class="form-control" id="date" name="date" value="{{ now()->format('Y-m-d') }}" required>
                  </div>
                  <button type="submit" class="btn btn-primary">
                    <i class="ti ti-file-download me-2"></i>Générer le rapport PDF
                  </button>
                </form>
                <form action="{{ route('reports.daily.excel', $project) }}" method="GET">
                  <input type="hidden" name="date" id="date_excel_daily" value="{{ now()->format('Y-m-d') }}">
                  <button type="submit" class="btn btn-success">
                    <i class="ti ti-file-spreadsheet me-2"></i>Exporter en Excel
                  </button>
                </form>
                <script>
                  document.getElementById('date').addEventListener('change', function() {
                    document.getElementById('date_excel_daily').value = this.value;
                  });
                </script>
              </div>
            </div>
          </div>

          <div class="col-md-6">
            <div class="card bg-light-success">
              <div class="card-body">
                <h6 class="fw-semibold mb-3">Rapport Hebdomadaire</h6>
                <form action="{{ route('reports.weekly', $project) }}" method="GET" class="mb-3">
                  <div class="mb-3">
                    <label for="start_date" class="form-label">Date de début</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="{{ now()->startOfWeek()->format('Y-m-d') }}" required>
                  </div>
                  <div class="mb-3">
                    <label for="end_date" class="form-label">Date de fin</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="{{ now()->endOfWeek()->format('Y-m-d') }}" required>
                  </div>
                  <button type="submit" class="btn btn-success">
                    <i class="ti ti-file-download me-2"></i>Générer le rapport PDF
                  </button>
                </form>
                <form action="{{ route('reports.weekly.excel', $project) }}" method="GET">
                  <input type="hidden" name="start_date" id="start_date_excel" value="{{ now()->startOfWeek()->format('Y-m-d') }}">
                  <input type="hidden" name="end_date" id="end_date_excel" value="{{ now()->endOfWeek()->format('Y-m-d') }}">
                  <button type="submit" class="btn btn-success">
                    <i class="ti ti-file-spreadsheet me-2"></i>Exporter en Excel
                  </button>
                </form>
                <script>
                  document.getElementById('start_date').addEventListener('change', function() {
                    document.getElementById('start_date_excel').value = this.value;
                  });
                  document.getElementById('end_date').addEventListener('change', function() {
                    document.getElementById('end_date_excel').value = this.value;
                  });
                </script>
              </div>
            </div>
          </div>
        </div>

        <hr class="my-4">

        <h6 class="fw-semibold mb-3">Historique des rapports</h6>
        <div class="table-responsive">
          <table class="table text-nowrap mb-0 align-middle">
            <thead class="text-dark fs-4">
              <tr>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Type</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Période</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Créé par</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Date de création</h6>
                </th>
              </tr>
            </thead>
            <tbody>
              @forelse($reports as $report)
                <tr>
                  <td class="border-bottom-0">
                    <span class="badge bg-{{ $report->type == 'journalier' ? 'primary' : 'success' }} rounded-3 fw-semibold">
                      {{ $report->type_label }}
                    </span>
                  </td>
                  <td class="border-bottom-0">
                    @if($report->type == 'journalier')
                      <p class="mb-0 fw-normal">{{ $report->report_date->format('d/m/Y') }}</p>
                    @else
                      <p class="mb-0 fw-normal">
                        {{ $report->report_date->format('d/m/Y') }} - {{ $report->end_date->format('d/m/Y') }}
                      </p>
                    @endif
                  </td>
                  <td class="border-bottom-0">
                    <p class="mb-0 fw-normal">{{ $report->creator->name ?? 'N/A' }}</p>
                  </td>
                  <td class="border-bottom-0">
                    <p class="mb-0 fw-normal">{{ $report->created_at->format('d/m/Y à H:i') }}</p>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="4" class="text-center py-4">
                    <p class="mb-0">Aucun rapport généré pour le moment.</p>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        @if($reports->hasPages())
          <div class="mt-4">
            {{ $reports->links() }}
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection

