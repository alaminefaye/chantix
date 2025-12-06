@extends('layouts.app')

@section('title', 'Inviter un collaborateur - ' . $company->name)

@section('content')
<div class="row">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h5 class="card-title fw-semibold mb-0">Inviter un collaborateur - {{ $company->name }}</h5>
          <a href="{{ route('invitations.index', $company) }}" class="btn btn-secondary">Retour</a>
        </div>

        <form action="{{ route('invitations.store', $company) }}" method="POST" id="invitationForm">
          @csrf

          <div class="mb-3">
            <div class="form-check form-switch mb-3">
              <input class="form-check-input" type="checkbox" id="create_directly" name="create_directly" value="1" {{ old('create_directly', true) ? 'checked' : '' }}>
              <label class="form-check-label fw-semibold" for="create_directly">
                Créer directement l'utilisateur (sans invitation par email)
              </label>
            </div>
          </div>

          <div class="mb-3" id="nameField" style="display: none;">
            <label for="name" class="form-label">Nom complet <span class="text-danger">*</span></label>
            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" placeholder="Prénom Nom">
            @error('name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label for="email" class="form-label">Email du collaborateur <span class="text-danger">*</span></label>
            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required placeholder="exemple@email.com">
            <small class="text-muted" id="emailHelp">Un email d'invitation sera envoyé à cette adresse</small>
            @error('email')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3" id="passwordField" style="display: none;">
            <label for="password" class="form-label">Mot de passe <span class="text-danger">*</span></label>
            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" placeholder="Minimum 8 caractères">
            <small class="text-muted">Le mot de passe sera communiqué au collaborateur</small>
            @error('password')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label for="role_id" class="form-label">Rôle <span class="text-danger">*</span></label>
            <select class="form-select @error('role_id') is-invalid @enderror" id="role_id" name="role_id" required>
              <option value="">Sélectionner un rôle</option>
              @foreach($roles as $role)
                <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                  {{ $role->display_name ?? ucfirst($role->name) }} - {{ $role->description ?? '' }}
                </option>
              @endforeach
            </select>
            @error('role_id')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label for="project_id" class="form-label">Projet <span class="text-muted">(optionnel)</span></label>
            <select class="form-select @error('project_id') is-invalid @enderror" id="project_id" name="project_id">
              <option value="">Tous les projets de l'entreprise</option>
              @foreach($projects as $project)
                <option value="{{ $project->id }}" {{ old('project_id') == $project->id ? 'selected' : '' }}>
                  {{ $project->name }}
                </option>
              @endforeach
            </select>
            <small class="text-muted">Si un projet est sélectionné, l'utilisateur n'aura accès qu'à ce projet. Sinon, il aura accès à tous les projets de l'entreprise.</small>
            @error('project_id')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label for="message" class="form-label">Message personnalisé (optionnel)</label>
            <textarea class="form-control @error('message') is-invalid @enderror" id="message" name="message" rows="3" placeholder="Message à inclure...">{{ old('message') }}</textarea>
            @error('message')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="alert alert-info" id="infoAlert">
            <h6 class="fw-semibold mb-2">
              <i class="ti ti-info-circle me-2"></i>Comment ça fonctionne ?
            </h6>
            <ul class="mb-0" id="infoList">
              <li>Un email sera envoyé au collaborateur avec un lien d'invitation unique</li>
              <li>Le lien est valide pendant 7 jours</li>
              <li>Si le collaborateur n'a pas encore de compte, il pourra en créer un</li>
              <li>Si le collaborateur a déjà un compte, il rejoindra directement l'entreprise</li>
            </ul>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary" id="submitBtn">
              <i class="ti ti-send me-2"></i>Envoyer l'invitation
            </button>
            <a href="{{ route('invitations.index', $company) }}" class="btn btn-secondary">Annuler</a>
          </div>
        </form>

        <script>
          document.addEventListener('DOMContentLoaded', function() {
            const createDirectly = document.getElementById('create_directly');
            const nameField = document.getElementById('nameField');
            const passwordField = document.getElementById('passwordField');
            const emailHelp = document.getElementById('emailHelp');
            const infoList = document.getElementById('infoList');
            const submitBtn = document.getElementById('submitBtn');
            const nameInput = document.getElementById('name');
            const passwordInput = document.getElementById('password');

            createDirectly.addEventListener('change', function() {
              if (this.checked) {
                nameField.style.display = 'block';
                passwordField.style.display = 'block';
                nameInput.required = true;
                passwordInput.required = true;
                emailHelp.textContent = 'Si l\'utilisateur existe déjà, il sera ajouté directement. Sinon, un compte sera créé.';
                infoList.innerHTML = '<li>L\'utilisateur sera créé ou ajouté immédiatement</li><li>Si l\'utilisateur existe déjà, il sera ajouté directement à l\'entreprise</li><li>Si l\'utilisateur n\'existe pas, un compte sera créé avec le mot de passe fourni</li><li>Aucun email ne sera envoyé</li>';
                submitBtn.innerHTML = '<i class="ti ti-user-plus me-2"></i>Créer et ajouter l\'utilisateur';
              } else {
                nameField.style.display = 'none';
                passwordField.style.display = 'none';
                nameInput.required = false;
                passwordInput.required = false;
                emailHelp.textContent = 'Un email d\'invitation sera envoyé à cette adresse';
                infoList.innerHTML = '<li>Un email sera envoyé au collaborateur avec un lien d\'invitation unique</li><li>Le lien est valide pendant 7 jours</li><li>Si le collaborateur n\'a pas encore de compte, il pourra en créer un</li><li>Si le collaborateur a déjà un compte, il rejoindra directement l\'entreprise</li>';
                submitBtn.innerHTML = '<i class="ti ti-send me-2"></i>Envoyer l\'invitation';
              }
            });

            // Initialiser l'état au chargement
            if (createDirectly.checked) {
              createDirectly.dispatchEvent(new Event('change'));
            }
          });
        </script>
      </div>
    </div>
  </div>
</div>
@endsection

