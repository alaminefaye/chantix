@extends('layouts.app')

@section('title', 'Chat - ' . $project->name)

@section('content')
<div class="row">
  <div class="col-lg-10">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h5 class="card-title fw-semibold mb-0">
            <i class="ti ti-message-circle me-2"></i>Chat - {{ $project->name }}
          </h5>
          <a href="{{ route('projects.show', $project) }}" class="btn btn-secondary">Retour</a>
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

        @if($errors->any())
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
              @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        @endif

        <!-- Formulaire de nouveau commentaire -->
        <div class="card bg-light mb-4">
          <div class="card-body">
            <form action="{{ route('comments.store', $project) }}" method="POST" id="commentForm" enctype="multipart/form-data">
              @csrf
              <div class="mb-3">
                <label for="content" class="form-label">Nouveau message</label>
                <textarea 
                  class="form-control @error('content') is-invalid @enderror" 
                  id="content" 
                  name="content" 
                  rows="3" 
                  placeholder="Tapez votre message... Utilisez @nom pour mentionner un utilisateur"
                >{{ old('content') }}</textarea>
                <small class="text-muted">Astuce: Utilisez @nom pour mentionner un utilisateur</small>
                @error('content')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              
              <div class="mb-3">
                <label for="attachments" class="form-label">Pièces jointes (optionnel)</label>
                <input type="file" class="form-control @error('attachments.*') is-invalid @enderror" id="attachments" name="attachments[]" multiple accept="image/*,.pdf,.doc,.docx">
                <small class="text-muted">Photos, PDF, Documents (max 10MB par fichier)</small>
                @error('attachments.*')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div id="attachmentsPreview" class="mt-2"></div>
              </div>
              
              <button type="submit" class="btn btn-primary">
                <i class="ti ti-send me-2"></i>Envoyer
              </button>
            </form>
          </div>
        </div>

        <!-- Liste des commentaires -->
        <div class="comments-section">
          @forelse($comments as $comment)
            <div class="card mb-3 comment-item">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                  <div class="d-flex align-items-center">
                    <div class="avatar-sm me-2">
                      <div class="avatar-title bg-primary text-white rounded-circle">
                        {{ strtoupper(substr($comment->user->name, 0, 1)) }}
                      </div>
                    </div>
                    <div>
                      <h6 class="mb-0 fw-semibold">{{ $comment->user->name }}</h6>
                      <small class="text-muted">{{ $comment->created_at->diffForHumans() }}</small>
                    </div>
                  </div>
                  @if($comment->user_id == auth()->id() || auth()->user()->hasRoleInCompany('admin', auth()->user()->current_company_id))
                    <form action="{{ route('comments.destroy', ['project' => $project, 'comment' => $comment]) }}" method="POST" onsubmit="return confirm('Supprimer ce commentaire ?');" class="d-inline">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-link text-danger">
                        <i class="ti ti-trash"></i>
                      </button>
                    </form>
                  @endif
                </div>
                
                <div class="comment-content mb-3">
                  @if($comment->content)
                    {!! nl2br(e($comment->content)) !!}
                  @endif
                  
                  @if($comment->attachments && count($comment->attachments) > 0)
                    <div class="mt-3">
                      <h6 class="fw-semibold mb-2" style="font-size: 0.9rem;">Pièces jointes:</h6>
                      <div class="d-flex flex-wrap gap-2">
                        @foreach($comment->attachments as $attachment)
                          @php
                            $isImage = str_starts_with($attachment['type'] ?? '', 'image/');
                            $fileUrl = Storage::url($attachment['path']);
                          @endphp
                          @if($isImage)
                            <a href="{{ $fileUrl }}" target="_blank" class="text-decoration-none">
                              <img src="{{ $fileUrl }}" alt="{{ $attachment['name'] ?? 'Image' }}" class="img-thumbnail" style="max-width: 150px; max-height: 150px; object-fit: cover;">
                            </a>
                          @else
                            <div class="card border" style="width: 150px;">
                              <div class="card-body text-center p-2">
                                <i class="ti ti-file" style="font-size: 32px; color: #ccc;"></i>
                                <p class="mb-0 text-truncate" style="font-size: 0.75rem;" title="{{ $attachment['name'] ?? 'Fichier' }}">
                                  {{ $attachment['name'] ?? 'Fichier' }}
                                </p>
                                <a href="{{ $fileUrl }}" target="_blank" class="btn btn-sm btn-outline-primary mt-1">
                                  <i class="ti ti-download"></i>
                                </a>
                              </div>
                            </div>
                          @endif
                        @endforeach
                      </div>
                    </div>
                  @endif
                  
                  @if($comment->mentioned_users && count($comment->mentioned_users) > 0)
                    <div class="mt-2">
                      @foreach($comment->mentioned_users as $userId)
                        @php
                          $mentionedUser = \App\Models\User::find($userId);
                        @endphp
                        @if($mentionedUser)
                          <span class="badge bg-info me-1">@{{ $mentionedUser->name }}</span>
                        @endif
                      @endforeach
                    </div>
                  @endif
                </div>

                <!-- Formulaire de réponse -->
                <button class="btn btn-sm btn-outline-primary reply-btn" data-comment-id="{{ $comment->id }}">
                  <i class="ti ti-reply me-1"></i>Répondre
                </button>

                <div class="reply-form mt-3" id="reply-form-{{ $comment->id }}" style="display: none;">
                  <form action="{{ route('comments.store', $project) }}" method="POST" class="reply-form-submit" data-comment-id="{{ $comment->id }}">
                    @csrf
                    <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                    <div class="mb-2">
                      <textarea 
                        class="form-control form-control-sm" 
                        name="content" 
                        rows="2" 
                        placeholder="Votre réponse..."
                        required
                      ></textarea>
                    </div>
                    <div class="d-flex gap-2">
                      <button type="submit" class="btn btn-sm btn-primary">Envoyer</button>
                      <button type="button" class="btn btn-sm btn-secondary cancel-reply" data-comment-id="{{ $comment->id }}">Annuler</button>
                    </div>
                  </form>
                </div>

                <!-- Réponses -->
                @if($comment->replies->count() > 0)
                  <div class="replies mt-3 ms-4 border-start border-2 ps-3">
                    @foreach($comment->replies as $reply)
                      <div class="card mb-2 bg-light">
                        <div class="card-body p-3">
                          <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="d-flex align-items-center">
                              <div class="avatar-xs me-2">
                                <div class="avatar-title bg-secondary text-white rounded-circle" style="width: 24px; height: 24px; font-size: 10px;">
                                  {{ strtoupper(substr($reply->user->name, 0, 1)) }}
                                </div>
                              </div>
                              <div>
                                <h6 class="mb-0" style="font-size: 0.9rem;">{{ $reply->user->name }}</h6>
                                <small class="text-muted" style="font-size: 0.75rem;">{{ $reply->created_at->diffForHumans() }}</small>
                              </div>
                            </div>
                            @if($reply->user_id == auth()->id() || auth()->user()->hasRoleInCompany('admin', auth()->user()->current_company_id))
                              <form action="{{ route('comments.destroy', ['project' => $project, 'comment' => $reply]) }}" method="POST" onsubmit="return confirm('Supprimer cette réponse ?');" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-link text-danger" style="padding: 0; font-size: 0.8rem;">
                                  <i class="ti ti-trash"></i>
                                </button>
                              </form>
                            @endif
                          </div>
                          <div class="reply-content">
                            @if($reply->content)
                              {!! nl2br(e($reply->content)) !!}
                            @endif
                            
                            @if($reply->attachments && count($reply->attachments) > 0)
                              <div class="mt-2">
                                <div class="d-flex flex-wrap gap-1">
                                  @foreach($reply->attachments as $attachment)
                                    @php
                                      $isImage = str_starts_with($attachment['type'] ?? '', 'image/');
                                      $fileUrl = Storage::url($attachment['path']);
                                    @endphp
                                    @if($isImage)
                                      <a href="{{ $fileUrl }}" target="_blank" class="text-decoration-none">
                                        <img src="{{ $fileUrl }}" alt="{{ $attachment['name'] ?? 'Image' }}" class="img-thumbnail" style="max-width: 80px; max-height: 80px; object-fit: cover;">
                                      </a>
                                    @else
                                      <div class="border rounded p-1 text-center" style="width: 80px;">
                                        <i class="ti ti-file" style="font-size: 20px; color: #ccc;"></i>
                                        <small class="d-block text-truncate" style="font-size: 0.65rem; max-width: 80px;" title="{{ $attachment['name'] ?? 'Fichier' }}">
                                          {{ $attachment['name'] ?? 'Fichier' }}
                                        </small>
                                        <a href="{{ $fileUrl }}" target="_blank" class="btn btn-xs btn-outline-primary mt-1" style="font-size: 0.6rem; padding: 2px 4px;">
                                          <i class="ti ti-download"></i>
                                        </a>
                                      </div>
                                    @endif
                                  @endforeach
                                </div>
                              </div>
                            @endif
                            
                            @if($reply->mentioned_users && count($reply->mentioned_users) > 0)
                              <div class="mt-1">
                                @foreach($reply->mentioned_users as $userId)
                                  @php
                                    $mentionedUser = \App\Models\User::find($userId);
                                  @endphp
                                  @if($mentionedUser)
                                    <span class="badge bg-info me-1" style="font-size: 0.7rem;">@{{ $mentionedUser->name }}</span>
                                  @endif
                                @endforeach
                              </div>
                            @endif
                          </div>
                        </div>
                      </div>
                    @endforeach
                  </div>
                @endif
              </div>
            </div>
          @empty
            <div class="text-center py-5">
              <i class="ti ti-message-circle-off" style="font-size: 48px; color: #ccc;"></i>
              <p class="text-muted mt-3">Aucun commentaire pour le moment. Soyez le premier à commenter !</p>
            </div>
          @endforelse
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gérer les boutons de réponse
    document.querySelectorAll('.reply-btn').forEach(button => {
        button.addEventListener('click', function() {
            const commentId = this.getAttribute('data-comment-id');
            const replyForm = document.getElementById('reply-form-' + commentId);
            replyForm.style.display = replyForm.style.display === 'none' ? 'block' : 'none';
        });
    });

    // Gérer les boutons d'annulation
    document.querySelectorAll('.cancel-reply').forEach(button => {
        button.addEventListener('click', function() {
            const commentId = this.getAttribute('data-comment-id');
            const replyForm = document.getElementById('reply-form-' + commentId);
            replyForm.style.display = 'none';
            replyForm.querySelector('textarea').value = '';
        });
    });

    // Auto-resize textarea
    const textarea = document.getElementById('content');
    if (textarea) {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    }

    // Preview des pièces jointes
    const attachmentsInput = document.getElementById('attachments');
    const attachmentsPreview = document.getElementById('attachmentsPreview');
    
    if (attachmentsInput && attachmentsPreview) {
        attachmentsInput.addEventListener('change', function(e) {
            attachmentsPreview.innerHTML = '';
            
            if (e.target.files && e.target.files.length > 0) {
                Array.from(e.target.files).forEach((file) => {
                    const div = document.createElement('div');
                    div.className = 'd-inline-block me-2 mb-2';
                    
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            div.innerHTML = `
                                <img src="${e.target.result}" alt="${file.name}" class="img-thumbnail" style="max-width: 100px; max-height: 100px; object-fit: cover;">
                                <small class="d-block text-truncate" style="max-width: 100px;">${file.name}</small>
                            `;
                        };
                        reader.readAsDataURL(file);
                    } else {
                        div.innerHTML = `
                            <div class="border rounded p-2 text-center" style="width: 100px;">
                                <i class="ti ti-file" style="font-size: 24px; color: #ccc;"></i>
                                <small class="d-block text-truncate" style="max-width: 100px;" title="${file.name}">${file.name}</small>
                            </div>
                        `;
                    }
                    
                    attachmentsPreview.appendChild(div);
                });
            }
        });
    }
});
</script>

<style>
.comment-item {
    transition: all 0.3s ease;
}

.comment-item:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.avatar-sm {
    width: 40px;
    height: 40px;
}

.avatar-xs {
    width: 24px;
    height: 24px;
}

.avatar-title {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    font-weight: bold;
}

.comment-content {
    white-space: pre-wrap;
    word-wrap: break-word;
}

.replies {
    border-color: #e0e0e0 !important;
}
</style>
@endsection

