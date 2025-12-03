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
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Les données fournies sont invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // TODO: Gérer l'upload de photos, vidéos et audio
        // Pour l'instant, on crée seulement avec les données texte

        $update = ProgressUpdate::create([
            'project_id' => $projectId,
            'user_id' => $user->id,
            'progress_percentage' => $request->progress,
            'description' => $request->description,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        // Mettre à jour le pourcentage d'avancement du projet
        $project->progress = $request->progress;
        if ($request->progress == 100) {
            $project->status = 'termine';
        } elseif ($request->progress > 0 && $project->status == 'non_demarre') {
            $project->status = 'en_cours';
        }
        $project->save();

        // Formater la réponse
        $formattedUpdate = [
            'id' => $update->id,
            'project_id' => $update->project_id,
            'user_id' => $update->user_id,
            'progress' => $update->progress_percentage,
            'description' => $update->description,
            'latitude' => $update->latitude ? (float)$update->latitude : null,
            'longitude' => $update->longitude ? (float)$update->longitude : null,
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

