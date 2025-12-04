@extends('layouts.app')

@section('title', 'Détails du rôle')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h5 class="card-title fw-semibold mb-0">Détails du rôle : {{ $role->display_name }}</h5>
          <div class="d-flex gap-2">
            <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-primary">
              <i class="ti ti-edit me-2"></i>Modifier
            </a>
            <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">
              <i class="ti ti-arrow-left me-2"></i>Retour
            </a>
          </div>
        </div>

        <div class="row">
          <div class="col-md-6">
            <h6 class="fw-semibold mb-3">Informations générales</h6>
            <table class="table table-borderless">
              <tr>
                <th width="150">Nom technique :</th>
                <td><span class="badge bg-primary">{{ $role->name }}</span></td>
              </tr>
              <tr>
                <th>Nom d'affichage :</th>
                <td>{{ $role->display_name }}</td>
              </tr>
              <tr>
                <th>Description :</th>
                <td>{{ $role->description ?? '-' }}</td>
              </tr>
              <tr>
                <th>Nombre d'utilisateurs :</th>
                <td><span class="badge bg-secondary">{{ $role->companyUsers()->count() }} utilisateur(s)</span></td>
              </tr>
            </table>
          </div>

          <div class="col-md-6">
            <h6 class="fw-semibold mb-3">
              Permissions 
              @if($role->permissions && count($role->permissions) > 0)
                <span class="badge bg-success">{{ count($role->permissions) }} permission(s)</span>
              @endif
            </h6>
            @if($role->permissions && count($role->permissions) > 0)
              <div class="border rounded p-3 bg-light" style="max-height: 400px; overflow-y: auto;">
                @php
                  $groupedPermissions = [];
                  foreach($role->permissions as $permission) {
                    $parts = explode('.', $permission);
                    $category = $parts[0] ?? 'other';
                    if (!isset($groupedPermissions[$category])) {
                      $groupedPermissions[$category] = [];
                    }
                    $groupedPermissions[$category][] = $permission;
                  }
                @endphp
                @foreach($groupedPermissions as $category => $perms)
                  <div class="mb-4 p-3 bg-white rounded border border-success">
                    <h6 class="text-primary mb-3 fw-semibold">
                      <i class="ti ti-folder me-2"></i>{{ ucfirst($category) }}
                      <span class="badge bg-info ms-2">{{ count($perms) }}</span>
                    </h6>
                    <ul class="list-unstyled ms-2">
                      @foreach($perms as $perm)
                        <li class="mb-2 p-2 rounded" style="background-color: #d1e7dd;">
                          <i class="ti ti-check-circle text-success me-2 fs-5"></i>
                          <strong>{{ $availablePermissions[$category][$perm] ?? $perm }}</strong>
                          <br>
                          <code class="text-muted ms-4">{{ $perm }}</code>
                        </li>
                      @endforeach
                    </ul>
                  </div>
                @endforeach
              </div>
            @else
              <div class="alert alert-warning">
                <i class="ti ti-alert-triangle me-2"></i>
                Aucune permission assignée à ce rôle
              </div>
            @endif
          </div>
        </div>

        @if($role->companyUsers()->count() > 0)
          <div class="mt-4">
            <h6 class="fw-semibold mb-3">Utilisateurs avec ce rôle</h6>
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Entreprise</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($role->companyUsers()->with('companies')->get() as $user)
                    <tr>
                      <td>{{ $user->name }}</td>
                      <td>{{ $user->email }}</td>
                      <td>
                        @foreach($user->companies as $company)
                          <span class="badge bg-info">{{ $company->name }}</span>
                        @endforeach
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection

