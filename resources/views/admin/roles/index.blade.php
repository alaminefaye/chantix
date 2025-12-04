@extends('layouts.app')

@section('title', 'Rôles et Permissions')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h5 class="card-title fw-semibold mb-0">Rôles et Permissions</h5>
          <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">
            <i class="ti ti-plus me-2"></i>Créer un rôle
          </a>
        </div>

        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Nom</th>
                <th>Nom d'affichage</th>
                <th>Description</th>
                <th>Permissions</th>
                <th>Utilisateurs</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($roles as $role)
                <tr>
                  <td>
                    <span class="badge bg-primary">{{ $role->name }}</span>
                  </td>
                  <td>{{ $role->display_name }}</td>
                  <td>{{ $role->description ?? '-' }}</td>
                  <td>
                    @if($role->permissions && count($role->permissions) > 0)
                      <span class="badge bg-info">{{ count($role->permissions) }} permission(s)</span>
                    @else
                      <span class="text-muted">Aucune</span>
                    @endif
                  </td>
                  <td>
                    <span class="badge bg-secondary">{{ $role->users()->count() }} utilisateur(s)</span>
                  </td>
                  <td>
                    <div class="d-flex gap-2">
                      <a href="{{ route('admin.roles.show', $role) }}" class="btn btn-sm btn-info" title="Voir">
                        <i class="ti ti-eye"></i>
                      </a>
                      <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-sm btn-primary" title="Modifier">
                        <i class="ti ti-edit"></i>
                      </a>
                      @if($role->users()->count() === 0)
                        <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce rôle ?');">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="btn btn-sm btn-danger" title="Supprimer">
                            <i class="ti ti-trash"></i>
                          </button>
                        </form>
                      @endif
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="text-center text-muted py-4">
                    Aucun rôle trouvé.
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
@endsection

