<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserValidationController extends Controller
{
    /**
     * Afficher la liste des utilisateurs en attente de validation
     */
    public function index()
    {
        $user = Auth::user();
        
        // Seul le super admin peut accéder
        if (!$user->isSuperAdmin()) {
            abort(403, 'Accès réservé au super administrateur.');
        }

        $pendingUsers = User::where('is_verified', false)
            ->where('is_super_admin', false)
            ->with('companies')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $verifiedUsers = User::where('is_verified', true)
            ->where('is_super_admin', false)
            ->with('companies')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.users-validation', compact('pendingUsers', 'verifiedUsers'));
    }

    /**
     * Valider un utilisateur
     */
    public function verify(User $user)
    {
        $currentUser = Auth::user();
        
        if (!$currentUser->isSuperAdmin()) {
            abort(403, 'Accès réservé au super administrateur.');
        }

        if ($user->is_super_admin) {
            return redirect()->back()
                ->with('error', 'Impossible de valider un super administrateur.');
        }

        $user->is_verified = true;
        $user->save();

        // TODO: Envoyer un email à l'utilisateur pour l'informer que son compte est validé

        return redirect()->back()
            ->with('success', 'Utilisateur validé avec succès.');
    }

    /**
     * Rejeter/Supprimer un utilisateur
     */
    public function reject(User $user)
    {
        $currentUser = Auth::user();
        
        if (!$currentUser->isSuperAdmin()) {
            abort(403, 'Accès réservé au super administrateur.');
        }

        if ($user->is_super_admin) {
            return redirect()->back()
                ->with('error', 'Impossible de supprimer un super administrateur.');
        }

        // Supprimer l'utilisateur et ses entreprises
        $user->companies()->detach();
        $user->delete();

        return redirect()->back()
            ->with('success', 'Utilisateur rejeté et supprimé.');
    }
}
