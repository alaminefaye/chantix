@extends('layouts.app')

@section('title', 'Matériaux')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <div>
            <h5 class="card-title fw-semibold mb-0">Matériaux</h5>
            @if($lowStockCount > 0)
              <p class="text-danger mb-0 mt-1">
                <i class="ti ti-alert-triangle me-1"></i>
                {{ $lowStockCount }} matériau(x) avec stock faible
              </p>
            @endif
          </div>
          <div class="d-flex gap-2">
            @can('materials.import')
            <a href="{{ route('materials.import') }}" class="btn btn-info">
              <i class="ti ti-upload me-2"></i>Importer Excel
            </a>
            @endcan
            @can('materials.create')
            <a href="{{ route('materials.create') }}" class="btn btn-primary">
              <i class="ti ti-plus me-2"></i>Ajouter un matériau
            </a>
            @endcan
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

        <div class="table-responsive">
          <table class="table text-nowrap mb-0 align-middle">
            <thead class="text-dark fs-4">
              <tr>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Nom</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Catégorie</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Stock</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Prix unitaire</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Fournisseur</h6>
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
              @forelse($materials as $material)
                <tr>
                  <td class="border-bottom-0">
                    <h6 class="fw-semibold mb-0">{{ $material->name }}</h6>
                    @if($material->description)
                      <p class="mb-0 fw-normal text-muted" style="font-size: 0.85rem;">{{ Str::limit($material->description, 50) }}</p>
                    @endif
                  </td>
                  <td class="border-bottom-0">
                    @if($material->category)
                      <span class="badge bg-info rounded-3 fw-semibold">{{ $material->category }}</span>
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>
                  <td class="border-bottom-0">
                    <div class="d-flex align-items-center">
                      <span class="fw-semibold {{ $material->isLowStock() ? 'text-danger' : '' }}">
                        {{ number_format($material->stock_quantity, 2, ',', ' ') }} {{ $material->unit }}
                      </span>
                      @if($material->isLowStock())
                        <i class="ti ti-alert-triangle text-danger ms-2"></i>
                      @endif
                    </div>
                    @if($material->min_stock > 0)
                      <p class="mb-0 text-muted" style="font-size: 0.75rem;">Seuil: {{ number_format($material->min_stock, 2, ',', ' ') }} {{ $material->unit }}</p>
                    @endif
                  </td>
                  <td class="border-bottom-0">
                    <h6 class="fw-semibold mb-0">{{ number_format($material->unit_price, 2, ',', ' ') }} FCFA</h6>
                  </td>
                  <td class="border-bottom-0">
                    @if($material->supplier)
                      <p class="mb-0 fw-normal">{{ $material->supplier }}</p>
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>
                  <td class="border-bottom-0">
                    @if($material->is_active)
                      <span class="badge bg-success rounded-3 fw-semibold">Actif</span>
                    @else
                      <span class="badge bg-secondary rounded-3 fw-semibold">Inactif</span>
                    @endif
                  </td>
                  <td class="border-bottom-0">
                    <div class="d-flex align-items-center gap-2">
                      <a href="{{ route('materials.show', $material) }}" class="btn btn-sm btn-info">Voir</a>
                      <a href="{{ route('materials.edit', $material) }}" class="btn btn-sm btn-warning">Modifier</a>
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="text-center py-4">
                    <p class="mb-0">Aucun matériau trouvé. <a href="{{ route('materials.create') }}">Ajouter un matériau</a></p>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        @if($materials->hasPages())
          <div class="mt-4">
            {{ $materials->links() }}
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection

