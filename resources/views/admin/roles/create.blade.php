@extends('layouts.app')

@section('title', 'Créer un rôle')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h5 class="card-title fw-semibold mb-0">Créer un nouveau rôle</h5>
          <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">
            <i class="ti ti-arrow-left me-2"></i>Retour
          </a>
        </div>

        <form action="{{ route('admin.roles.store') }}" method="POST">
          @csrf

          <div class="mb-3">
            <label for="name" class="form-label">Nom du rôle <span class="text-danger">*</span></label>
            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
            <small class="form-text text-muted">Nom technique (ex: chef_chantier, ingenieur)</small>
            @error('name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label for="display_name" class="form-label">Nom d'affichage <span class="text-danger">*</span></label>
            <input type="text" class="form-control @error('display_name') is-invalid @enderror" id="display_name" name="display_name" value="{{ old('display_name') }}" required>
            <small class="form-text text-muted">Nom affiché dans l'interface (ex: Chef de Chantier, Ingénieur)</small>
            @error('display_name')
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

          <div class="mb-4">
            <label class="form-label">Permissions</label>
            <div class="border rounded p-3 bg-light" style="max-height: 500px; overflow-y: auto;">
              @foreach($availablePermissions as $category => $permissions)
                <div class="mb-4 p-3 bg-white rounded border">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-semibold text-primary mb-0">
                      <i class="ti ti-folder me-2"></i>{{ ucfirst($category) }}
                    </h6>
                    <button type="button" class="btn btn-sm btn-outline-primary toggle-category" data-category="{{ $category }}">
                      <i class="ti ti-check me-1"></i>Tout sélectionner
                    </button>
                  </div>
                  @foreach($permissions as $permission => $label)
                    <div class="form-check mb-3 p-2 rounded permission-item" style="background-color: #f8f9fa; border-left: 3px solid #dee2e6;">
                      <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="{{ $permission }}" id="perm_{{ $permission }}" {{ in_array($permission, old('permissions', [])) ? 'checked' : '' }} style="width: 20px; height: 20px; cursor: pointer;">
                      <label class="form-check-label ms-2" for="perm_{{ $permission }}" style="cursor: pointer; font-weight: 500;">
                        <span class="permission-label">{{ $label }}</span>
                        <br>
                        <small class="text-muted">
                          <code>{{ $permission }}</code>
                        </small>
                        <span class="badge bg-success ms-2 permission-badge" style="display: none;">
                          <i class="ti ti-check"></i> Sélectionné
                        </span>
                      </label>
                    </div>
                  @endforeach
                </div>
              @endforeach
            </div>
            <small class="form-text text-muted">
              <i class="ti ti-info-circle me-1"></i>
              Cochez les cases pour accorder les permissions. Les permissions sélectionnées sont marquées en vert.
            </small>
          </div>

          <style>
            .permission-item:hover {
              background-color: #e9ecef !important;
              border-left-color: #0d6efd !important;
            }
            .permission-checkbox:checked + label .permission-badge {
              display: inline-block !important;
            }
            .permission-checkbox:checked + label {
              color: #198754;
            }
            .permission-checkbox:checked + label .permission-label {
              font-weight: 600;
            }
          </style>

          <script>
            document.addEventListener('DOMContentLoaded', function() {
              // Afficher/masquer les badges pour les cases déjà cochées
              document.querySelectorAll('.permission-checkbox').forEach(function(checkbox) {
                updatePermissionBadge(checkbox);
                checkbox.addEventListener('change', function() {
                  updatePermissionBadge(this);
                });
              });

              // Boutons "Tout sélectionner / Tout désélectionner"
              document.querySelectorAll('.toggle-category').forEach(function(button) {
                button.addEventListener('click', function() {
                  const category = this.getAttribute('data-category');
                  const checkboxes = document.querySelectorAll(`input[type="checkbox"][id^="perm_"][value^="${category}."]`);
                  const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                  
                  checkboxes.forEach(function(cb) {
                    cb.checked = !allChecked;
                    updatePermissionBadge(cb);
                  });
                  
                  this.innerHTML = allChecked 
                    ? '<i class="ti ti-check me-1"></i>Tout sélectionner'
                    : '<i class="ti ti-x me-1"></i>Tout désélectionner';
                });
              });
            });

            function updatePermissionBadge(checkbox) {
              const label = checkbox.nextElementSibling;
              const badge = label.querySelector('.permission-badge');
              if (checkbox.checked) {
                badge.style.display = 'inline-block';
              } else {
                badge.style.display = 'none';
              }
            }
          </script>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="ti ti-check me-2"></i>Créer le rôle
            </button>
            <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">Annuler</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

