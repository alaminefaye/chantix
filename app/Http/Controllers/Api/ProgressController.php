<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProgressUpdate;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ProgressController extends Controller
{
    /**
     * Liste des mises à jour d'avancement pour un projet
     */
    public function index(Request $request, $projectId)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if (!$companyId) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez sélectionner une entreprise.',
            ], 400);
        }

        $project = Project::forCompany($companyId)->find($projectId);

        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Projet non trouvé.',
            ], 404);
        }

        $updates = ProgressUpdate::where('project_id', $projectId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        // Formater les données pour correspondre au modèle Flutter
        $formattedUpdates = $updates->map(function ($update) {
            $photos = [];
            if ($update->photos) {
                foreach ($update->photos as $photo) {
                    $photos[] = Storage::url($photo);
                }
            }

            $videos = [];
            if ($update->videos) {
                foreach ($update->videos as $video) {
                    $videos[] = Storage::url($video);
                }
            }

            return [
                'id' => $update->id,
                'project_id' => $update->project_id,
                'user_id' => $update->user_id,
                'progress' => $update->progress_percentage,
                'description' => $update->description,
                'audio_report' => $update->audio_file ? Storage::url($update->audio_file) : null,
                'latitude' => $update->latitude ? (float)$update->latitude : null,
                'longitude' => $update->longitude ? (float)$update->longitude : null,
                'photos' => !empty($photos) ? $photos : null,
                'videos' => !empty($videos) ? $videos : null,
                'created_at' => $update->created_at->toIso8601String(),
                'updated_at' => $update->updated_at->toIso8601String(),
                'user' => $update->user ? [
                    'id' => $update->user->id,
                    'name' => $update->user->name,
                    'email' => $update->user->email,
                ] : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedUpdates,
        ], 200);
    }

    /**
     * Créer une mise à jour d'avancement
     */
    public function store(Request $request, $projectId)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if (!$companyId) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez sélectionner une entreprise.',
            ], 400);
        }

        $project = Project::forCompany($companyId)->find($projectId);

        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Projet non trouvé.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'progress' => 'required|integer|min:0|max:100',
            'description' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'photos.*' => 'nullable|image|max:5120', // 5MB max
            'videos.*' => 'nullable|mimes:mp4,avi,mov|max:51200', // 50MB max
            'audio_report' => 'nullable|file|mimes:mp3,m4a,wav|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Les données fournies sont invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Gérer l'upload de photos
        $photos = [];
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $path = $photo->store('progress/photos', 'public');
                $photos[] = $path;
            }
        }

        // Gérer l'upload de vidéos
        $videos = [];
        if ($request->hasFile('videos')) {
            foreach ($request->file('videos') as $video) {
                $path = $video->store('progress/videos', 'public');
                $videos[] = $path;
            }
        }

        // Gérer l'upload de l'audio
        $audioFile = null;
        if ($request->hasFile('audio_report')) {
            $audioFile = $request->file('audio_report')->store('progress/audio', 'public');
        }

        // S'assurer que progress est un entier
        $progress = (int)$request->progress;

        $update = ProgressUpdate::create([
            'project_id' => $projectId,
            'user_id' => $user->id,
            'progress_percentage' => $progress,
            'description' => $request->description,
            'latitude' => $request->latitude ? (float)$request->latitude : null,
            'longitude' => $request->longitude ? (float)$request->longitude : null,
            'photos' => !empty($photos) ? $photos : null,
            'videos' => !empty($videos) ? $videos : null,
            'audio_file' => $audioFile,
        ]);

        // Mettre à jour le pourcentage d'avancement du projet
        $project->progress = $progress;
        if ($progress == 100) {
            $project->status = 'termine';
        } elseif ($progress > 0 && $project->status == 'non_demarre') {
            $project->status = 'en_cours';
        }
        $project->save();

        // Formater les photos et vidéos pour la réponse
        $formattedPhotos = [];
        if ($update->photos) {
            foreach ($update->photos as $photo) {
                $formattedPhotos[] = Storage::url($photo);
            }
        }

        $formattedVideos = [];
        if ($update->videos) {
            foreach ($update->videos as $video) {
                $formattedVideos[] = Storage::url($video);
            }
        }

        // Formater la réponse avec conversion explicite des types
        $formattedUpdate = [
            'id' => (int)$update->id,
            'project_id' => (int)$update->project_id,
            'user_id' => (int)$update->user_id,
            'progress' => (int)$update->progress_percentage,
            'description' => $update->description,
            'latitude' => $update->latitude !== null ? (float)$update->latitude : null,
            'longitude' => $update->longitude !== null ? (float)$update->longitude : null,
            'photos' => !empty($formattedPhotos) ? $formattedPhotos : null,
            'videos' => !empty($formattedVideos) ? $formattedVideos : null,
            'audio_report' => $update->audio_file ? Storage::url($update->audio_file) : null,
            'created_at' => $update->created_at->toIso8601String(),
            'updated_at' => $update->updated_at->toIso8601String(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Mise à jour d\'avancement créée avec succès.',
            'data' => $formattedUpdate,
        ], 201);
    }

    /**
     * Mettre à jour une mise à jour d'avancement
     */
    public function update(Request $request, $projectId, $progressId)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if (!$companyId) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez sélectionner une entreprise.',
            ], 400);
        }

        $project = Project::forCompany($companyId)->find($projectId);

        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Projet non trouvé.',
            ], 404);
        }

        $update = ProgressUpdate::where('project_id', $projectId)
            ->where('id', $progressId)
            ->first();

        if (!$update) {
            return response()->json([
                'success' => false,
                'message' => 'Mise à jour non trouvée.',
            ], 404);
        }

        // Vérifier les permissions
        $canUpdate = $update->user_id == $user->id
            || $user->isSuperAdmin()
            || $user->hasRoleInCompany('admin', $companyId)
            || $user->hasPermission('progress.update', $companyId);
        
        if (!$canUpdate) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas la permission de modifier cette mise à jour.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'progress' => 'required|integer|min:0|max:100',
            'description' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'photos.*' => 'nullable|image|max:5120',
            'videos.*' => 'nullable|mimes:mp4,avi,mov|max:51200',
            'audio_report' => 'nullable|file|mimes:mp3,m4a,wav|max:10240',
            'existing_photos' => 'nullable|array',
            'existing_videos' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Les données fournies sont invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Gérer les photos existantes à conserver
        $photos = [];
        if ($request->has('existing_photos')) {
            $existingPhotos = $request->input('existing_photos', []);
            // Si c'est un tableau, itérer dessus
            if (is_array($existingPhotos)) {
                foreach ($existingPhotos as $photoPath) {
                    // Extraire le chemin relatif si c'est une URL complète
                    $relativePath = str_replace('/storage/', '', $photoPath);
                    $relativePath = preg_replace('#^https?://[^/]+/#', '', $relativePath);
                    // Vérifier si le fichier existe
                    if (Storage::disk('public')->exists($relativePath)) {
                        $photos[] = $relativePath;
                    }
                }
            }
        }

        // Ajouter les nouvelles photos
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $path = $photo->store('progress/photos', 'public');
                $photos[] = $path;
            }
        }

        // Gérer les vidéos existantes à conserver
        $videos = [];
        if ($request->has('existing_videos')) {
            $existingVideos = $request->input('existing_videos', []);
            // Si c'est un tableau, itérer dessus
            if (is_array($existingVideos)) {
                foreach ($existingVideos as $videoPath) {
                    // Extraire le chemin relatif si c'est une URL complète
                    $relativePath = str_replace('/storage/', '', $videoPath);
                    $relativePath = preg_replace('#^https?://[^/]+/#', '', $relativePath);
                    // Vérifier si le fichier existe
                    if (Storage::disk('public')->exists($relativePath)) {
                        $videos[] = $relativePath;
                    }
                }
            }
        }

        // Ajouter les nouvelles vidéos
        if ($request->hasFile('videos')) {
            foreach ($request->file('videos') as $video) {
                $path = $video->store('progress/videos', 'public');
                $videos[] = $path;
            }
        }

        // Gérer l'audio
        $audioFile = $update->audio_file;
        if ($request->hasFile('audio_report')) {
            // Supprimer l'ancien fichier audio s'il existe
            if ($audioFile) {
                Storage::disk('public')->delete($audioFile);
            }
            $audioFile = $request->file('audio_report')->store('progress/audio', 'public');
        }

        // Supprimer les photos/vidéos qui ne sont plus dans la liste
        if ($update->photos) {
            foreach ($update->photos as $oldPhoto) {
                if (!in_array($oldPhoto, $photos)) {
                    Storage::disk('public')->delete($oldPhoto);
                }
            }
        }

        if ($update->videos) {
            foreach ($update->videos as $oldVideo) {
                if (!in_array($oldVideo, $videos)) {
                    Storage::disk('public')->delete($oldVideo);
                }
            }
        }

        // S'assurer que progress est un entier
        $progress = (int)$request->progress;

        // Mettre à jour la mise à jour
        $update->update([
            'progress_percentage' => $progress,
            'description' => $request->description,
            'latitude' => $request->latitude ? (float)$request->latitude : null,
            'longitude' => $request->longitude ? (float)$request->longitude : null,
            'photos' => !empty($photos) ? $photos : null,
            'videos' => !empty($videos) ? $videos : null,
            'audio_file' => $audioFile,
        ]);

        // Mettre à jour le pourcentage d'avancement du projet
        $project->progress = $progress;
        if ($progress == 100) {
            $project->status = 'termine';
        } elseif ($progress > 0 && $project->status == 'non_demarre') {
            $project->status = 'en_cours';
        }
        $project->save();

        // Formater les photos et vidéos pour la réponse
        $formattedPhotos = [];
        if ($update->photos) {
            foreach ($update->photos as $photo) {
                $formattedPhotos[] = Storage::url($photo);
            }
        }

        $formattedVideos = [];
        if ($update->videos) {
            foreach ($update->videos as $video) {
                $formattedVideos[] = Storage::url($video);
            }
        }

        // Formater la réponse
        $formattedUpdate = [
            'id' => (int)$update->id,
            'project_id' => (int)$update->project_id,
            'user_id' => (int)$update->user_id,
            'progress' => (int)$update->progress_percentage,
            'description' => $update->description,
            'latitude' => $update->latitude !== null ? (float)$update->latitude : null,
            'longitude' => $update->longitude !== null ? (float)$update->longitude : null,
            'photos' => !empty($formattedPhotos) ? $formattedPhotos : null,
            'videos' => !empty($formattedVideos) ? $formattedVideos : null,
            'audio_report' => $update->audio_file ? Storage::url($update->audio_file) : null,
            'created_at' => $update->created_at->toIso8601String(),
            'updated_at' => $update->updated_at->toIso8601String(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Mise à jour modifiée avec succès.',
            'data' => $formattedUpdate,
        ], 200);
    }

    /**
     * Supprimer une mise à jour d'avancement
     */
    public function destroy($projectId, $progressId)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if (!$companyId) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez sélectionner une entreprise.',
            ], 400);
        }

        $project = Project::forCompany($companyId)->find($projectId);

        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Projet non trouvé.',
            ], 404);
        }

        $update = ProgressUpdate::where('project_id', $projectId)
            ->where('id', $progressId)
            ->first();

        if (!$update) {
            return response()->json([
                'success' => false,
                'message' => 'Mise à jour non trouvée.',
            ], 404);
        }

        // Vérifier les permissions : l'utilisateur peut supprimer si :
        // - Il est le créateur de la mise à jour
        // - Il est super admin
        // - Il est admin de l'entreprise
        // - Il a la permission progress.update
        $canDelete = $update->user_id == $user->id
            || $user->isSuperAdmin()
            || $user->hasRoleInCompany('admin', $companyId)
            || $user->hasPermission('progress.update', $companyId);
        
        if (!$canDelete) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas la permission de supprimer cette mise à jour.',
            ], 403);
        }

        // Supprimer les fichiers associés
        if ($update->photos) {
            foreach ($update->photos as $photo) {
                Storage::disk('public')->delete($photo);
            }
        }

        if ($update->videos) {
            foreach ($update->videos as $video) {
                Storage::disk('public')->delete($video);
            }
        }

        if ($update->audio_file) {
            Storage::disk('public')->delete($update->audio_file);
        }

        $update->delete();

        return response()->json([
            'success' => true,
            'message' => 'Mise à jour supprimée avec succès.',
        ], 200);
    }
}

