@extends('layouts.app')

@section('title', $material->name)

@section('content')
<div class="row">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h5 class="card-title fw-semibold mb-0">{{ $material->name }}</h5>
          <div class="d-flex gap-2">
            <a href="{{ route('materials.edit', $material) }}" class="btn btn-warning">
              <i class="ti ti-edit me-2"></i>Modifier
            </a>
            <a href="{{ route('materials.index') }}" class="btn btn-secondary">Retour</a>
          </div>
        </div>

        @if(session('success'))
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        @endif

        <div class="row mb-4">
          <div class="col-md-6">
            <h6 class="fw-semibold mb-2">Statut</h6>
            @if($material->is_active)
              <span class="badge bg-success rounded-3 fw-semibold fs-4">Actif</span>
            @else
              <span class="badge bg-secondary rounded-3 fw-semibold fs-4">Inactif</span>
            @endif
          </div>
          <div class="col-md-6">
            <h6 class="fw-semibold mb-2">Stock</h6>
            <div class="d-flex align-items-center">
              <span class="fw-semibold fs-4 {{ $material->isLowStock() ? 'text-danger' : '' }}">
                {{ number_format($material->stock_quantity, 2, ',', ' ') }} {{ $material->unit }}
              </span>
              @if($material->isLowStock())
                <i class="ti ti-alert-triangle text-danger ms-2 fs-4"></i>
                <span class="badge bg-danger ms-2">Stock faible</span>
              @endif
            </div>
            @if($material->min_stock > 0)
              <p class="mb-0 text-muted">Seuil minimum: {{ number_format($material->min_stock, 2, ',', ' ') }} {{ $material->unit }}</p>
            @endif
          </div>
        </div>

        <div class="row mb-4">
          <div class="col-md-6">
            <h6 class="fw-semibold mb-2">Catégorie</h6>
            @if($material->category)
              <span class="badge bg-info rounded-3 fw-semibold">{{ $material->category }}</span>
            @else
              <span class="text-muted">Non définie</span>
            @endif
          </div>
          <div class="col-md-6">
            <h6 class="fw-semibold mb-2">Prix unitaire</h6>
            <p class="mb-0 fs-4">{{ number_format($material->unit_price, 2, ',', ' ') }} FCFA</p>
          </div>
        </div>

        @if($material->description)
          <div class="mb-4">
            <h6 class="fw-semibold mb-2">Description</h6>
            <p class="mb-0">{{ $material->description }}</p>
          </div>
        @endif

        <div class="row mb-4">
          <div class="col-md-6">
            <h6 class="fw-semibold mb-2">Fournisseur</h6>
            <p class="mb-0">{{ $material->supplier ?? '-' }}</p>
          </div>
          <div class="col-md-6">
            <h6 class="fw-semibold mb-2">Référence</h6>
            <p class="mb-0">{{ $material->reference ?? '-' }}</p>
          </div>
        </div>

        <hr class="my-4">

        <h6 class="fw-semibold mb-3">Projets utilisant ce matériau</h6>
        @if($material->projects->count() > 0)
          <div class="table-responsive">
            <table class="table text-nowrap mb-0 align-middle">
              <thead class="text-dark fs-4">
                <tr>
                  <th class="border-bottom-0">
                    <h6 class="fw-semibold mb-0">Projet</h6>
                  </th>
                  <th class="border-bottom-0">
                    <h6 class="fw-semibold mb-0">Prévu</h6>
                  </th>
                  <th class="border-bottom-0">
                    <h6 class="fw-semibold mb-0">Commandé</h6>
                  </th>
                  <th class="border-bottom-0">
                    <h6 class="fw-semibold mb-0">Livré</h6>
                  </th>
                  <th class="border-bottom-0">
                    <h6 class="fw-semibold mb-0">Utilisé</h6>
                  </th>
                  <th class="border-bottom-0">
                    <h6 class="fw-semibold mb-0">Restant</h6>
                  </th>
                </tr>
              </thead>
              <tbody>
                @foreach($material->projects as $project)
                  @php
                    $pivot = $project->pivot;
                  @endphp
                  <tr>
                    <td class="border-bottom-0">
                      <a href="{{ route('projects.show', $project) }}" class="text-primary">
                        {{ $project->name }}
                      </a>
                    </td>
                    <td class="border-bottom-0">
                      {{ number_format($pivot->quantity_planned, 2, ',', ' ') }} {{ $material->unit }}
                    </td>
                    <td class="border-bottom-0">
                      {{ number_format($pivot->quantity_ordered, 2, ',', ' ') }} {{ $material->unit }}
                    </td>
                    <td class="border-bottom-0">
                      {{ number_format($pivot->quantity_delivered, 2, ',', ' ') }} {{ $material->unit }}
                    </td>
                    <td class="border-bottom-0">
                      @php
                        $isOverConsumption = $pivot->quantity_used > $pivot->quantity_planned;
                      @endphp
                      <span class="{{ $isOverConsumption ? 'text-danger fw-bold' : '' }}">
                        {{ number_format($pivot->quantity_used, 2, ',', ' ') }} {{ $material->unit }}
                      </span>
                      @if($isOverConsumption)
                        <i class="ti ti-alert-triangle text-danger ms-1"></i>
                      @endif
                    </td>
                    <td class="border-bottom-0">
                      {{ number_format($pivot->quantity_remaining, 2, ',', ' ') }} {{ $material->unit }}
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @else
          <p class="text-muted">Ce matériau n'est utilisé dans aucun projet.</p>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection

