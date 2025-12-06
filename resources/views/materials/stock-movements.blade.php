@extends('layouts.app')

@section('title', 'Mouvements de stock - ' . $material->name)

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <div>
            <h5 class="card-title fw-semibold mb-0">Mouvements de stock</h5>
            <p class="text-muted mb-0">Matériau: <strong>{{ $material->name }}</strong></p>
            <p class="text-muted mb-0">Stock actuel: <strong class="{{ $material->isLowStock() ? 'text-danger' : '' }}">{{ number_format($material->stock_quantity, 2, ',', ' ') }} {{ $material->unit }}</strong></p>
          </div>
          <div>
            <a href="{{ route('materials.show', $material) }}" class="btn btn-secondary">
              <i class="ti ti-arrow-left me-2"></i>Retour
            </a>
          </div>
        </div>

        @if(session('success'))
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        @endif

        <div class="table-responsive">
          <table class="table text-nowrap mb-0 align-middle">
            <thead class="text-dark fs-4">
              <tr>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Date</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Type</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Quantité</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Stock avant</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Stock après</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Projet</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Raison</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Utilisateur</h6>
                </th>
              </tr>
            </thead>
            <tbody>
              @forelse($movements as $movement)
                <tr>
                  <td class="border-bottom-0">
                    <p class="mb-0 fw-normal">{{ $movement->created_at->format('d/m/Y H:i') }}</p>
                  </td>
                  <td class="border-bottom-0">
                    @if($movement->type === 'in')
                      <span class="badge bg-success rounded-3 fw-semibold">Entrée</span>
                    @elseif($movement->type === 'out')
                      <span class="badge bg-danger rounded-3 fw-semibold">Sortie</span>
                    @else
                      <span class="badge bg-warning rounded-3 fw-semibold">Ajustement</span>
                    @endif
                  </td>
                  <td class="border-bottom-0">
                    <span class="fw-semibold {{ $movement->quantity >= 0 ? 'text-success' : 'text-danger' }}">
                      {{ $movement->quantity >= 0 ? '+' : '' }}{{ number_format($movement->quantity, 2, ',', ' ') }} {{ $material->unit }}
                    </span>
                  </td>
                  <td class="border-bottom-0">
                    <p class="mb-0 fw-normal">{{ number_format($movement->stock_before, 2, ',', ' ') }} {{ $material->unit }}</p>
                  </td>
                  <td class="border-bottom-0">
                    <p class="mb-0 fw-semibold">{{ number_format($movement->stock_after, 2, ',', ' ') }} {{ $material->unit }}</p>
                  </td>
                  <td class="border-bottom-0">
                    @if($movement->project)
                      <a href="{{ route('projects.show', $movement->project) }}" class="text-primary">
                        {{ $movement->project->name }}
                      </a>
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>
                  <td class="border-bottom-0">
                    <p class="mb-0 fw-normal">{{ $movement->reason ?? '-' }}</p>
                    @if($movement->notes)
                      <small class="text-muted">{{ Str::limit($movement->notes, 50) }}</small>
                    @endif
                  </td>
                  <td class="border-bottom-0">
                    @if($movement->user)
                      <p class="mb-0 fw-normal">{{ $movement->user->name }}</p>
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="8" class="text-center py-4">
                    <p class="mb-0 text-muted">Aucun mouvement de stock enregistré.</p>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        @if($movements->hasPages())
          <div class="mt-4">
            {{ $movements->links() }}
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection

