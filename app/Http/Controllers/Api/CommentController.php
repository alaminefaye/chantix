<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class CommentController extends Controller
{
    /**
     * Liste des commentaires d'un projet
     */
    public function index(Request $request, $projectId)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if (!$companyId && !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez sélectionner une entreprise.',
            ], 400);
        }

        $project = Project::find($projectId);
        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Projet non trouvé.',
            ], 404);
        }

        if ($project->company_id !== $companyId && !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        $query = Comment::forProject($projectId)
            ->main()
            ->with(['user', 'replies' => function($q) {
                $q->with('user')->orderBy('created_at', 'asc');
            }]);

        // Tri
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $comments = $query->get();

        return response()->json([
            'success' => true,
            'data' => $comments,
        ], 200);
    }

    /**
     * Détails d'un commentaire
     */
    public function show($id, $projectId)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if (!$companyId && !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez sélectionner une entreprise.',
            ], 400);
        }

        $project = Project::find($projectId);
        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Projet non trouvé.',
            ], 404);
        }

        if ($project->company_id !== $companyId && !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        $comment = Comment::with(['user', 'replies.user', 'project'])
            ->find($id);

        if (!$comment || $comment->project_id != $projectId) {
            return response()->json([
                'success' => false,
                'message' => 'Commentaire non trouvé.',
            ], 404);
        }

        return response()->json($comment, 200);
    }

    /**
     * Créer un commentaire
     */
    public function store(Request $request, $projectId)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if (!$companyId && !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez sélectionner une entreprise.',
            ], 400);
        }

        $project = Project::find($projectId);
        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Projet non trouvé.',
            ], 404);
        }

        if ($project->company_id !== $companyId && !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'required_without:attachments|nullable|string|max:5000',
            'parent_id' => 'nullable|exists:comments,id',
            'attachments.*' => 'nullable|file|mimes:jpg,jpeg,png,gif,pdf,doc,docx|max:10240',
        ], [
            'content.required_without' => 'Le contenu ou une pièce jointe est requis.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Vérifier que le parent appartient au même projet
        if ($request->filled('parent_id')) {
            $parent = Comment::find($request->parent_id);
            if (!$parent || $parent->project_id != $projectId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commentaire parent invalide.',
                ], 422);
            }
        }

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
        if (empty($request->input('content')) && empty($attachments)) {
            return response()->json([
                'success' => false,
                'message' => 'Le contenu ou une pièce jointe est requis.',
            ], 422);
        }

        // Extraire les mentions
        $mentionedUsers = $this->extractMentions(
            $request->input('content', ''),
            $project
        );

        $comment = Comment::create([
            'project_id' => $projectId,
            'user_id' => $user->id,
            'parent_id' => $request->input('parent_id'),
            'content' => $request->input('content', ''),
            'mentioned_users' => !empty($mentionedUsers) ? $mentionedUsers : null,
            'attachments' => !empty($attachments) ? $attachments : null,
        ]);

        // Recharger avec toutes les relations
        $comment->load(['user', 'replies' => function($q) {
            $q->with('user')->orderBy('created_at', 'asc');
        }]);

        return response()->json($comment, 201);
    }

    /**
     * Supprimer un commentaire
     */
    public function destroy($id, $projectId)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if (!$companyId && !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez sélectionner une entreprise.',
            ], 400);
        }

        $project = Project::find($projectId);
        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Projet non trouvé.',
            ], 404);
        }

        if ($project->company_id !== $companyId && !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        $comment = Comment::find($id);
        if (!$comment || $comment->project_id != $projectId) {
            return response()->json([
                'success' => false,
                'message' => 'Commentaire non trouvé.',
            ], 404);
        }

        // Vérifier que l'utilisateur peut supprimer (créateur ou admin)
        if ($comment->user_id != $user->id && !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas la permission de supprimer ce commentaire.',
            ], 403);
        }

        // Supprimer les pièces jointes
        if ($comment->attachments) {
            foreach ($comment->attachments as $attachment) {
                if (isset($attachment['path'])) {
                    Storage::disk('public')->delete($attachment['path']);
                }
            }
        }

        $comment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Commentaire supprimé avec succès.',
        ], 200);
    }

    /**
     * Extraire les mentions (@username) du contenu
     */
    private function extractMentions($content, $project)
    {
        preg_match_all('/@(\w+)/', $content, $matches);
        $usernames = $matches[1] ?? [];

        if (empty($usernames)) {
            return [];
        }

        // Trouver les utilisateurs mentionnés dans l'entreprise du projet
        $users = User::where('company_id', $project->company_id)
            ->whereIn('name', $usernames)
            ->pluck('id')
            ->toArray();

        return $users;
    }
}

