<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CommentController extends Controller
{
    /**
     * Afficher les commentaires d'un projet
     */
    public function index(Project $project)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if ($project->company_id !== $companyId) {
            abort(403, 'Accès non autorisé.');
        }

        $comments = $project->comments()
            ->with(['user', 'replies' => function($query) {
                $query->with('user')->orderBy('created_at', 'asc');
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        // Marquer les commentaires comme lus
        Comment::where('project_id', $project->id)
            ->where('user_id', '!=', $user->id)
            ->update(['is_read' => true]);

        return view('comments.index', compact('project', 'comments'));
    }

    /**
     * Créer un nouveau commentaire
     */
    public function store(Request $request, Project $project)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if ($project->company_id !== $companyId) {
            abort(403, 'Accès non autorisé.');
        }

        $validated = $request->validate([
            'content' => 'required_without:attachments|nullable|string|max:5000',
            'parent_id' => 'nullable|exists:comments,id',
            'attachments.*' => 'nullable|file|mimes:jpg,jpeg,png,gif,pdf,doc,docx|max:10240',
        ], [
            'content.required_without' => 'Le contenu ou une pièce jointe est requis.',
        ]);

        // Upload des pièces jointes
        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('comments/attachments', 'public');
                $attachments[] = [
                    'path' => $path,
                    'name' => $file->getClientOriginalName(),
                    'type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ];
            }
        }

        // Vérifier qu'il y a au moins du contenu ou des pièces jointes
        if (empty($validated['content']) && empty($attachments)) {
            return redirect()->route('comments.index', $project)
                ->with('error', 'Le contenu ou une pièce jointe est requis.');
        }

        // Extraire les mentions
        $mentionedUsers = $this->extractMentions($validated['content'] ?? '', $project);

        $comment = Comment::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'parent_id' => $validated['parent_id'] ?? null,
            'content' => $validated['content'] ?? '',
            'mentioned_users' => $mentionedUsers,
            'attachments' => !empty($attachments) ? $attachments : null,
        ]);
        
        // Recharger les relations
        $comment->load('user', 'replies.user');

        // Envoyer des notifications aux utilisateurs mentionnés
        if (!empty($mentionedUsers)) {
            foreach ($mentionedUsers as $mentionedUserId) {
                \App\Models\Notification::create([
                    'user_id' => $mentionedUserId,
                    'project_id' => $project->id,
                    'type' => 'mention',
                    'title' => 'Vous avez été mentionné',
                    'message' => $user->name . ' vous a mentionné dans un commentaire sur le projet "' . $project->name . '"',
                    'link' => route('comments.index', $project),
                    'data' => [
                        'comment_id' => $comment->id,
                        'mentioned_by' => $user->id,
                    ],
                ]);
            }
        }

        // Notifier les autres membres de l'entreprise (sauf l'auteur)
        $companyUsers = $project->company->users()
            ->where('users.id', '!=', $user->id)
            ->pluck('users.id')
            ->toArray();

        foreach ($companyUsers as $memberId) {
            // Ne pas notifier si déjà mentionné
            if (!in_array($memberId, $mentionedUsers)) {
                \App\Models\Notification::create([
                    'user_id' => $memberId,
                    'project_id' => $project->id,
                    'type' => 'comment',
                    'title' => 'Nouveau commentaire',
                    'message' => $user->name . ' a ajouté un commentaire sur le projet "' . $project->name . '"',
                    'link' => route('comments.index', $project),
                    'data' => [
                        'comment_id' => $comment->id,
                    ],
                ]);
            }
        }

        return redirect()->route('comments.index', $project)
            ->with('success', 'Commentaire ajouté avec succès.');
    }

    /**
     * Supprimer un commentaire
     */
    public function destroy(Project $project, Comment $comment)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if ($project->company_id !== $companyId || $comment->project_id !== $project->id) {
            abort(403, 'Accès non autorisé.');
        }

        // Seul l'auteur ou un admin peut supprimer
        if ($comment->user_id !== $user->id && !$user->hasRoleInCompany('admin', $companyId)) {
            abort(403, 'Vous n\'êtes pas autorisé à supprimer ce commentaire.');
        }

        // Supprimer aussi les réponses
        $comment->replies()->delete();
        $comment->delete();

        return redirect()->route('comments.index', $project)
            ->with('success', 'Commentaire supprimé avec succès.');
    }

    /**
     * Extraire les mentions @username du contenu
     */
    private function extractMentions($content, Project $project)
    {
        // Extraire toutes les mentions @nom
        preg_match_all('/@([a-zA-Z0-9_]+)/', $content, $matches);
        $mentions = $matches[1] ?? [];

        $mentionedUserIds = [];
        
        // Récupérer tous les utilisateurs de l'entreprise
        $companyUsers = $project->company->users;
        
        foreach ($mentions as $mention) {
            // Chercher par nom exact ou partiel (insensible à la casse)
            $user = $companyUsers->first(function ($user) use ($mention) {
                $nameParts = explode(' ', strtolower($user->name));
                $mentionLower = strtolower($mention);
                
                // Vérifier si le nom complet ou une partie correspond
                return strtolower($user->name) === $mentionLower 
                    || in_array($mentionLower, $nameParts)
                    || strpos(strtolower($user->name), $mentionLower) !== false;
            });
            
            if ($user && !in_array($user->id, $mentionedUserIds)) {
                $mentionedUserIds[] = $user->id;
            }
        }

        return array_unique($mentionedUserIds);
    }
}
