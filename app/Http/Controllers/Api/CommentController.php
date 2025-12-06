<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Project;
use App\Models\User;
use App\Services\PushNotificationService;
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

        // S'assurer que les réponses sont bien chargées
        foreach ($comments as $comment) {
            if ($comment->replies->isEmpty()) {
                // Recharger les réponses si elles ne sont pas chargées
                $comment->load('replies.user');
            }
        }

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

        // Envoyer des notifications push
        // 1. Notifier les utilisateurs mentionnés
        if (!empty($mentionedUsers)) {
            foreach ($mentionedUsers as $mentionedUserId) {
                // Créer notification DB
                \App\Models\Notification::create([
                    'user_id' => $mentionedUserId,
                    'project_id' => $project->id,
                    'type' => 'mention',
                    'title' => 'Vous avez été mentionné',
                    'message' => $user->name . ' vous a mentionné dans un commentaire sur le projet "' . $project->name . '"',
                    'link' => '/projects/' . $project->id . '/comments',
                    'data' => [
                        'comment_id' => $comment->id,
                        'mentioned_by' => $user->id,
                    ],
                ]);

                // Envoyer notification push
                try {
                    $pushService = new PushNotificationService();
                    $pushService->sendToUser(
                        $mentionedUserId,
                        'Vous avez été mentionné',
                        $user->name . ' vous a mentionné dans un commentaire sur le projet "' . $project->name . '"',
                        [
                            'type' => 'mention',
                            'comment_id' => $comment->id,
                            'project_id' => $project->id,
                            'mentioned_by' => $user->id,
                        ]
                    );
                } catch (\Exception $e) {
                    \Log::warning("Failed to send mention push notification: " . $e->getMessage());
                }
            }
        }

        // 2. Notifier les autres membres du projet (sauf l'auteur et les mentionnés)
        try {
            $pushService = new PushNotificationService();
            $contentPreview = $request->input('content') ? substr($request->input('content'), 0, 100) . '...' : 'Pièce jointe';
            $pushService->notifyProjectStakeholders(
                $project,
                'comment',
                'Nouveau commentaire',
                $user->name . ' a ajouté un commentaire sur le projet "' . $project->name . '"',
                [
                    'comment_id' => $comment->id,
                    'comment_content' => $contentPreview,
                    'has_attachments' => !empty($attachments),
                ],
                array_merge([$user->id], $mentionedUsers) // Exclure l'auteur et les mentionnés
            );
            \Log::info('📬 Comment notification process completed.');
        } catch (\Exception $e) {
            \Log::warning("Failed to send comment push notification: " . $e->getMessage());
        }

        // 3. Créer notifications DB pour les autres membres
        $companyUsers = $project->company->users()
            ->where('users.id', '!=', $user->id)
            ->pluck('users.id')
            ->toArray();

        foreach ($companyUsers as $memberId) {
            if (!in_array($memberId, $mentionedUsers)) {
                \App\Models\Notification::create([
                    'user_id' => $memberId,
                    'project_id' => $project->id,
                    'type' => 'comment',
                    'title' => 'Nouveau commentaire',
                    'message' => $user->name . ' a ajouté un commentaire sur le projet "' . $project->name . '"',
                    'link' => '/projects/' . $project->id . '/comments',
                    'data' => [
                        'comment_id' => $comment->id,
                    ],
                ]);
            }
        }

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

