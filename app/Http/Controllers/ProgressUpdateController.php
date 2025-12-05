<?php

namespace App\Http\Controllers;

use App\Models\ProgressUpdate;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProgressUpdateController extends Controller
{
    /**
     * Afficher les mises à jour d'un projet
     */
    public function index(Project $project)
    {
        $user = Auth::user();
        
        // Vérifier l'accès
        if ($project->company_id !== $user->current_company_id) {
            abort(403);
        }

        $updates = $project->progressUpdates()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('progress.index', compact('project', 'updates'));
    }

    /**
     * Afficher le formulaire de création
     */
    public function create(Project $project)
    {
        $user = Auth::user();
        
        if ($project->company_id !== $user->current_company_id) {
            abort(403);
        }

        return view('progress.create', compact('project'));
    }

    /**
     * Créer une mise à jour d'avancement
     */
    public function store(Request $request, Project $project)
    {
        $user = Auth::user();
        
        if ($project->company_id !== $user->current_company_id) {
            abort(403);
        }

        $request->validate([
            'progress_percentage' => 'required|integer|min:0|max:100',
            'description' => 'nullable|string',
            'photos.*' => 'nullable|image|max:5120', // 5MB max
            'videos.*' => 'nullable|mimes:mp4,avi,mov|max:51200', // 50MB max
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $photos = [];
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $path = $photo->store('progress/photos', 'public');
                $photos[] = $path;
            }
        }

        $videos = [];
        if ($request->hasFile('videos')) {
            foreach ($request->file('videos') as $video) {
                $path = $video->store('progress/videos', 'public');
                $videos[] = $path;
            }
        }

        $update = ProgressUpdate::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'progress_percentage' => $request->progress_percentage,
            'description' => $request->description,
            'photos' => !empty($photos) ? $photos : null,
            'videos' => !empty($videos) ? $videos : null,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        // Mettre à jour le pourcentage d'avancement du projet
        $project->progress = $request->progress_percentage;
        if ($request->progress_percentage == 100) {
            $project->status = 'termine';
        } elseif ($request->progress_percentage > 0 && $project->status == 'non_demarre') {
            $project->status = 'en_cours';
        }
        $project->save();

        return redirect()->route('progress.index', $project)
            ->with('success', 'Mise à jour d\'avancement créée avec succès !');
    }

    /**
     * Afficher une mise à jour
     */
    public function show(Project $project, ProgressUpdate $progressUpdate)
    {
        $user = Auth::user();
        
        // Vérifier que le projet appartient à l'entreprise de l'utilisateur
        if ($project->company_id !== $user->current_company_id) {
            abort(403, 'Vous n\'avez pas accès à ce projet.');
        }
        
        // Récupérer la mise à jour directement depuis la relation du projet
        // Cela garantit qu'elle appartient bien au projet (sinon 404)
        $progressUpdate = $project->progressUpdates()->with('user')->findOrFail($progressUpdate->id);

        return view('progress.show', compact('project', 'progressUpdate'));
    }

    /**
     * Supprimer une mise à jour
     */
    public function destroy(Project $project, ProgressUpdate $progressUpdate)
    {
        $user = Auth::user();
        
        // Vérifier que le projet appartient à l'entreprise de l'utilisateur
        if ($project->company_id !== $user->current_company_id) {
            abort(403, 'Vous n\'avez pas accès à ce projet.');
        }
        
        // Récupérer la mise à jour directement depuis la relation du projet
        // Cela garantit qu'elle appartient bien au projet (sinon 404)
        $progressUpdate = $project->progressUpdates()->findOrFail($progressUpdate->id);
        
        // Vérifier les permissions : l'utilisateur peut supprimer si :
        // - Il est le créateur de la mise à jour
        // - Il est super admin
        // - Il est admin de l'entreprise
        // - Il a la permission progress.update
        $canDelete = $progressUpdate->user_id == $user->id
            || $user->isSuperAdmin()
            || $user->hasRoleInCompany('admin', $project->company_id)
            || $user->hasPermission('progress.update', $project->company_id);
        
        if (!$canDelete) {
            \Log::warning('Tentative de suppression sans permission', [
                'user_id' => $user->id,
                'progress_update_user_id' => $progressUpdate->user_id,
                'project_id' => $project->id,
                'progress_update_id' => $progressUpdate->id,
            ]);
            
            abort(403, 'Vous n\'avez pas la permission de supprimer cette mise à jour. Seul le créateur, un admin ou un utilisateur avec la permission progress.update peut la supprimer.');
        }

        // Supprimer les fichiers
        if ($progressUpdate->photos) {
            foreach ($progressUpdate->photos as $photo) {
                Storage::disk('public')->delete($photo);
            }
        }
        if ($progressUpdate->videos) {
            foreach ($progressUpdate->videos as $video) {
                Storage::disk('public')->delete($video);
            }
        }
        
        if ($progressUpdate->audio_file) {
            Storage::disk('public')->delete($progressUpdate->audio_file);
        }

        $progressUpdate->delete();

        return redirect()->route('progress.index', $project)
            ->with('success', 'Mise à jour supprimée avec succès !');
    }
}
