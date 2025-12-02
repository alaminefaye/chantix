@extends('layouts.app')

@section('title', 'Modifier la tâche - ' . $project->name)

@section('content')
<div class="row">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h5 class="card-title fw-semibold mb-0">Modifier la tâche - {{ $project->name }}</h5>
          <a href="{{ route('tasks.index', $project) }}" class="btn btn-secondary">Retour</a>
        </div>

        <form action="{{ route('tasks.update', ['project' => $project, 'task' => $task]) }}" method="POST">
          @csrf
          @method('PUT')

          <div class="mb-3">
            <label for="title" class="form-label">Titre <span class="text-danger">*</span></label>
            <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $task->title) }}" required>
            @error('title')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $task->description) }}</textarea>
            @error('description')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="category" class="form-label">Catégorie</label>
              <select class="form-select @error('category') is-invalid @enderror" id="category" name="category">
                <option value="">Sélectionner une catégorie</option>
                <option value="maçonnerie" {{ old('category', $task->category) == 'maçonnerie' ? 'selected' : '' }}>Maçonnerie</option>
                <option value="fondations" {{ old('category', $task->category) == 'fondations' ? 'selected' : '' }}>Fondations</option>
                <option value="électricité" {{ old('category', $task->category) == 'électricité' ? 'selected' : '' }}>Électricité</option>
                <option value="plomberie" {{ old('category', $task->category) == 'plomberie' ? 'selected' : '' }}>Plomberie</option>
                <option value="peinture" {{ old('category', $task->category) == 'peinture' ? 'selected' : '' }}>Peinture</option>
                <option value="charpente" {{ old('category', $task->category) == 'charpente' ? 'selected' : '' }}>Charpente</option>
                <option value="isolation" {{ old('category', $task->category) == 'isolation' ? 'selected' : '' }}>Isolation</option>
                <option value="autres" {{ old('category', $task->category) == 'autres' ? 'selected' : '' }}>Autres</option>
              </select>
              @error('category')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6 mb-3">
              <label for="assigned_to" class="form-label">Assigné à</label>
              <select class="form-select @error('assigned_to') is-invalid @enderror" id="assigned_to" name="assigned_to">
                <option value="">Non assigné</option>
                @foreach($employees as $employee)
                  <option value="{{ $employee->id }}" {{ old('assigned_to', $task->assigned_to) == $employee->id ? 'selected' : '' }}>
                    {{ $employee->full_name }} @if($employee->position) - {{ $employee->position }} @endif
                  </option>
                @endforeach
              </select>
              @error('assigned_to')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="status" class="form-label">Statut <span class="text-danger">*</span></label>
              <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                <option value="a_faire" {{ old('status', $task->status) == 'a_faire' ? 'selected' : '' }}>À faire</option>
                <option value="en_cours" {{ old('status', $task->status) == 'en_cours' ? 'selected' : '' }}>En cours</option>
                <option value="termine" {{ old('status', $task->status) == 'termine' ? 'selected' : '' }}>Terminé</option>
                <option value="bloque" {{ old('status', $task->status) == 'bloque' ? 'selected' : '' }}>Bloqué</option>
              </select>
              @error('status')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6 mb-3">
              <label for="priority" class="form-label">Priorité <span class="text-danger">*</span></label>
              <select class="form-select @error('priority') is-invalid @enderror" id="priority" name="priority" required>
                <option value="basse" {{ old('priority', $task->priority) == 'basse' ? 'selected' : '' }}>Basse</option>
                <option value="moyenne" {{ old('priority', $task->priority) == 'moyenne' ? 'selected' : '' }}>Moyenne</option>
                <option value="haute" {{ old('priority', $task->priority) == 'haute' ? 'selected' : '' }}>Haute</option>
                <option value="urgente" {{ old('priority', $task->priority) == 'urgente' ? 'selected' : '' }}>Urgente</option>
              </select>
              @error('priority')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="start_date" class="form-label">Date de début</label>
              <input type="date" class="form-control @error('start_date') is-invalid @enderror" id="start_date" name="start_date" value="{{ old('start_date', $task->start_date?->format('Y-m-d')) }}">
              @error('start_date')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6 mb-3">
              <label for="deadline" class="form-label">Date limite (Deadline)</label>
              <input type="date" class="form-control @error('deadline') is-invalid @enderror" id="deadline" name="deadline" value="{{ old('deadline', $task->deadline?->format('Y-m-d')) }}">
              @error('deadline')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div class="mb-3">
            <label for="progress" class="form-label">Avancement (%)</label>
            <input type="number" min="0" max="100" class="form-control @error('progress') is-invalid @enderror" id="progress" name="progress" value="{{ old('progress', $task->progress) }}">
            @error('progress')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label for="notes" class="form-label">Notes</label>
            <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3">{{ old('notes', $task->notes) }}</textarea>
            @error('notes')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Mettre à jour</button>
            <a href="{{ route('tasks.index', $project) }}" class="btn btn-secondary">Annuler</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

