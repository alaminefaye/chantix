<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use App\Mail\InvitationMail;

class InvitationController extends Controller
{
    /**
     * Afficher les invitations d'une entreprise
     */
    public function index(Company $company)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        // Super admin peut accéder à toutes les entreprises
        if (!$user->canAccessCompanyResource($company->id)) {
            \Log::warning('Accès refusé - canAccessCompanyResource', [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'current_company_id' => $user->current_company_id,
                'is_super_admin' => $user->isSuperAdmin(),
                'user_companies' => $user->companies()->get()->pluck('id')->toArray(),
            ]);
            abort(403, 'Accès non autorisé. Vous n\'appartenez pas à cette entreprise.');
        }

        // Vérifier que l'utilisateur est admin ou super admin
        if (!$user->isSuperAdmin() && !$user->hasRoleInCompany('admin', $company->id)) {
            $role = $user->roleInCompany($company->id);
            \Log::warning('Accès refusé - Pas admin', [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'is_super_admin' => $user->isSuperAdmin(),
                'current_role' => $role ? $role->name : 'aucun',
            ]);
            abort(403, 'Seuls les administrateurs peuvent gérer les invitations. Votre rôle: ' . ($role ? $role->name : 'aucun'));
        }

        $invitations = $company->invitations()
            ->with('inviter', 'role', 'project')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('invitations.index', compact('company', 'invitations'));
    }

    /**
     * Afficher le formulaire d'invitation
     */
    public function create(Company $company)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        // Super admin peut accéder à toutes les entreprises
        if (!$user->canAccessCompanyResource($company->id)) {
            abort(403, 'Accès non autorisé.');
        }

        // Vérifier que l'utilisateur est admin ou super admin
        if (!$user->isSuperAdmin() && !$user->hasRoleInCompany('admin', $company->id)) {
            abort(403, 'Seuls les administrateurs peuvent inviter des utilisateurs.');
        }

        $roles = Role::all();
        $projects = $company->projects()->orderBy('name')->get();

        return view('invitations.create', compact('company', 'roles', 'projects'));
    }

    /**
     * Envoyer une invitation
     */
    public function store(Request $request, Company $company)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        // Super admin peut accéder à toutes les entreprises
        if (!$user->canAccessCompanyResource($company->id)) {
            abort(403, 'Accès non autorisé.');
        }

        // Vérifier que l'utilisateur est admin ou super admin
        if (!$user->isSuperAdmin() && !$user->hasRoleInCompany('admin', $company->id)) {
            abort(403, 'Seuls les administrateurs peuvent inviter des utilisateurs.');
        }

        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'role_id' => 'required|integer|exists:roles,id',
            'project_id' => 'nullable|integer|exists:projects,id',
            'message' => 'nullable|string|max:1000',
            'create_directly' => 'nullable|boolean', // Option pour créer directement
            'name' => 'nullable|string|max:255|required_if:create_directly,1',
            'password' => 'nullable|string|min:8|required_if:create_directly,1',
        ]);

        // Vérifier que le projet appartient bien à l'entreprise si un projet est sélectionné
        if (isset($validated['project_id']) && $validated['project_id']) {
            $project = \App\Models\Project::find($validated['project_id']);
            if (!$project || $project->company_id != $company->id) {
                return redirect()->back()
                    ->withErrors(['project_id' => 'Le projet sélectionné n\'appartient pas à cette entreprise.'])
                    ->withInput();
            }
        }
        
        // Convertir role_id en entier pour éviter les problèmes de type
        $validated['role_id'] = (int) $validated['role_id'];
        
        // Vérifier que le rôle existe vraiment
        $role = Role::find($validated['role_id']);
        if (!$role) {
            return redirect()->back()
                ->withErrors(['role_id' => 'Le rôle sélectionné n\'existe pas.'])
                ->withInput();
        }

        // Vérifier si l'utilisateur existe déjà dans l'entreprise
        $existingUser = User::where('email', $validated['email'])->first();
        if ($existingUser && $company->users()->where('users.id', $existingUser->id)->exists()) {
            return redirect()->back()
                ->with('error', 'Cet utilisateur fait déjà partie de l\'entreprise.');
        }

        // Si l'option "créer directement" est activée
        if ($request->has('create_directly') && $request->create_directly == '1') {
            // Si l'utilisateur existe déjà, l'ajouter directement à l'entreprise
            if ($existingUser) {
                // Vérifier qu'il n'est pas déjà dans l'entreprise
                if (!$company->users()->where('users.id', $existingUser->id)->exists()) {
                    // Vérifier automatiquement l'utilisateur s'il ne l'est pas déjà
                    if (!$existingUser->isVerified()) {
                        // Forcer la mise à jour avec update() pour être sûr
                        $existingUser->update([
                            'is_verified' => true,
                            'email_verified_at' => now(),
                        ]);
                        $existingUser->refresh();
                        
                        // Log pour déboguer
                        \Log::info('Utilisateur existant vérifié', [
                            'user_id' => $existingUser->id,
                            'email' => $existingUser->email,
                            'is_verified' => $existingUser->is_verified,
                            'is_verified_raw' => $existingUser->getRawOriginal('is_verified'),
                            'isVerified_method' => $existingUser->isVerified(),
                        ]);
                    }
                    
                    $company->users()->attach($existingUser->id, [
                        'role_id' => $validated['role_id'],
                        'is_active' => true,
                        'joined_at' => now(),
                    ]);

                    // Associer l'utilisateur au projet si un projet est spécifié
                    if (isset($validated['project_id']) && $validated['project_id']) {
                        $project = \App\Models\Project::find($validated['project_id']);
                        if ($project && !$project->users()->where('users.id', $existingUser->id)->exists()) {
                            $project->users()->attach($existingUser->id);
                        }
                    }

                    return redirect()->route('invitations.index', $company)
                        ->with('success', 'Utilisateur ajouté directement à l\'entreprise avec succès.');
                }
            } else {
                // Créer un nouvel utilisateur directement
                $newUser = new User();
                $newUser->name = $validated['name'];
                $newUser->email = $validated['email'];
                $newUser->password = Hash::make($validated['password']);
                $newUser->is_verified = true; // L'utilisateur créé directement est automatiquement vérifié
                $newUser->email_verified_at = now(); // Email vérifié automatiquement
                $newUser->save();
                
                // Forcer la mise à jour pour s'assurer que les valeurs sont bien sauvegardées
                $newUser->update([
                    'is_verified' => true,
                    'email_verified_at' => now(),
                ]);
                
                // Rafraîchir depuis la DB
                $newUser->refresh();
                
                // Log pour déboguer
                \Log::info('Utilisateur créé directement', [
                    'user_id' => $newUser->id,
                    'email' => $newUser->email,
                    'is_verified' => $newUser->is_verified,
                    'is_verified_raw' => $newUser->getRawOriginal('is_verified'),
                    'is_verified_type' => gettype($newUser->is_verified),
                    'email_verified_at' => $newUser->email_verified_at,
                    'isVerified_method' => $newUser->isVerified(),
                ]);

                // Ajouter l'utilisateur à l'entreprise
                $company->users()->attach($newUser->id, [
                    'role_id' => $validated['role_id'],
                    'is_active' => true,
                    'joined_at' => now(),
                ]);

                // Associer l'utilisateur au projet si un projet est spécifié
                if (isset($validated['project_id']) && $validated['project_id']) {
                    $project = \App\Models\Project::find($validated['project_id']);
                    if ($project && !$project->users()->where('users.id', $newUser->id)->exists()) {
                        $project->users()->attach($newUser->id);
                    }
                }

                // Créer une invitation marquée comme acceptée pour l'historique
                Invitation::create([
                    'company_id' => $company->id,
                    'project_id' => $validated['project_id'] ?? null,
                    'invited_by' => $user->id,
                    'role_id' => $validated['role_id'],
                    'email' => $validated['email'],
                    'token' => Invitation::generateToken(),
                    'status' => 'accepted',
                    'expires_at' => now()->addDays(7),
                    'accepted_at' => now(),
                    'message' => $validated['message'] ?? 'Compte créé directement',
                ]);

                return redirect()->route('invitations.index', $company)
                    ->with('success', 'Utilisateur créé et ajouté directement à l\'entreprise avec succès.');
            }
        }

        // Sinon, processus d'invitation normal
        // Vérifier si une invitation est déjà en attente
        $existingInvitation = Invitation::where('company_id', $company->id)
            ->where('email', $validated['email'])
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->first();

        if ($existingInvitation) {
            return redirect()->back()
                ->with('error', 'Une invitation est déjà en attente pour cet email.');
        }

        // Créer l'invitation
        $invitation = Invitation::create([
            'company_id' => $company->id,
            'project_id' => $validated['project_id'] ?? null,
            'invited_by' => $user->id,
            'role_id' => $validated['role_id'],
            'email' => $validated['email'],
            'token' => Invitation::generateToken(),
            'status' => 'pending',
            'expires_at' => now()->addDays(7), // Expire dans 7 jours
            'message' => $validated['message'] ?? null,
        ]);

        // Envoyer l'email d'invitation
        try {
            Mail::to($validated['email'])->send(new InvitationMail($invitation));
        } catch (\Exception $e) {
            // Log l'erreur mais continue
            \Log::error('Erreur envoi email invitation: ' . $e->getMessage());
        }

        return redirect()->route('invitations.index', $company)
            ->with('success', 'Invitation envoyée avec succès.');
    }

    /**
     * Accepter une invitation
     */
    public function accept($token)
    {
        $invitation = Invitation::where('token', $token)->firstOrFail();

        if (!$invitation->isValid()) {
            return redirect()->route('login')
                ->with('error', 'Cette invitation n\'est plus valide ou a expiré.');
        }

        // Vérifier si l'utilisateur est connecté
        if (!Auth::check()) {
            // Rediriger vers la page d'inscription avec le token
            return redirect()->route('register', ['token' => $token]);
        }

        $user = Auth::user();

        // Vérifier que l'email correspond
        if ($user->email !== $invitation->email) {
            return redirect()->route('dashboard')
                ->with('error', 'Cette invitation est destinée à un autre email.');
        }

        // Vérifier si l'utilisateur est déjà dans l'entreprise
        if ($invitation->company->users()->where('users.id', $user->id)->exists()) {
            $invitation->markAsAccepted();
            return redirect()->route('companies.index')
                ->with('info', 'Vous faites déjà partie de cette entreprise.');
        }

        // Ajouter l'utilisateur à l'entreprise
        $invitation->company->users()->attach($user->id, [
            'role_id' => $invitation->role_id,
            'is_active' => true,
            'joined_at' => now(),
        ]);

        // Associer l'utilisateur au projet si un projet est spécifié dans l'invitation
        if ($invitation->project_id) {
            $project = \App\Models\Project::find($invitation->project_id);
            if ($project && !$project->users()->where('users.id', $user->id)->exists()) {
                $project->users()->attach($user->id);
            }
        }

        // Définir l'entreprise comme actuelle si l'utilisateur n'en a pas
        if (!$user->current_company_id) {
            $user->current_company_id = $invitation->company_id;
            $user->save();
        }

        // Marquer l'invitation comme acceptée
        $invitation->markAsAccepted();

        return redirect()->route('dashboard')
            ->with('success', 'Vous avez rejoint l\'entreprise ' . $invitation->company->name . ' avec succès !');
    }

    /**
     * Afficher les détails d'une invitation
     */
    public function show(Company $company, Invitation $invitation)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        // Super admin peut accéder à toutes les entreprises
        $canAccess = $user->canAccessCompanyResource($company->id);
        $invitationMatches = (int) $invitation->company_id === (int) $company->id;
        
        if (!$canAccess || !$invitationMatches) {
            \Log::warning('Accès refusé - canAccessCompanyResource', [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'invitation_company_id' => $invitation->company_id,
                'current_company_id' => $user->current_company_id,
                'is_super_admin' => $user->isSuperAdmin(),
                'user_companies' => $user->companies()->get()->pluck('id')->toArray(),
                'can_access' => $canAccess,
                'invitation_matches' => $invitationMatches,
            ]);
            abort(403, 'Accès non autorisé. Vous n\'appartenez pas à cette entreprise.');
        }

        // Vérifier que l'utilisateur est admin ou super admin
        if (!$user->isSuperAdmin() && !$user->hasRoleInCompany('admin', $company->id)) {
            $role = $user->roleInCompany($company->id);
            \Log::warning('Accès refusé - Pas admin', [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'is_super_admin' => $user->isSuperAdmin(),
                'current_role' => $role ? $role->name : 'aucun',
            ]);
            abort(403, 'Seuls les administrateurs peuvent voir les détails des invitations. Votre rôle: ' . ($role ? $role->name : 'aucun'));
        }

        $invitation->load('inviter', 'role', 'company');

        return view('invitations.show', compact('company', 'invitation'));
    }

    /**
     * Afficher le formulaire de modification d'une invitation
     */
    public function edit(Company $company, Invitation $invitation)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        // Super admin peut accéder à toutes les entreprises
        $canAccess = $user->canAccessCompanyResource($company->id);
        $invitationMatches = (int) $invitation->company_id === (int) $company->id;
        
        if (!$canAccess || !$invitationMatches) {
            \Log::warning('Accès refusé - canAccessCompanyResource (edit)', [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'invitation_company_id' => $invitation->company_id,
                'current_company_id' => $user->current_company_id,
                'is_super_admin' => $user->isSuperAdmin(),
                'user_companies' => $user->companies()->get()->pluck('id')->toArray(),
                'can_access' => $canAccess,
                'invitation_matches' => $invitationMatches,
            ]);
            abort(403, 'Accès non autorisé. Vous n\'appartenez pas à cette entreprise.');
        }

        // Vérifier que l'utilisateur est admin ou super admin
        if (!$user->isSuperAdmin() && !$user->hasRoleInCompany('admin', $company->id)) {
            $role = $user->roleInCompany($company->id);
            \Log::warning('Accès refusé - Pas admin', [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'is_super_admin' => $user->isSuperAdmin(),
                'current_role' => $role ? $role->name : 'aucun',
            ]);
            abort(403, 'Seuls les administrateurs peuvent modifier des invitations. Votre rôle: ' . ($role ? $role->name : 'aucun'));
        }

        // Les invitations expirées ne peuvent pas être modifiées
        if ($invitation->isExpired()) {
            return redirect()->route('invitations.index', $company)
                ->with('error', 'Cette invitation a expiré et ne peut plus être modifiée.');
        }

        $roles = Role::all();
        $projects = $company->projects()->orderBy('name')->get();

        return view('invitations.edit', compact('company', 'invitation', 'roles', 'projects'));
    }

    /**
     * Mettre à jour une invitation
     */
    public function update(Request $request, Company $company, Invitation $invitation)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        // Super admin peut accéder à toutes les entreprises
        $canAccess = $user->canAccessCompanyResource($company->id);
        $invitationMatches = (int) $invitation->company_id === (int) $company->id;
        
        if (!$canAccess || !$invitationMatches) {
            abort(403, 'Accès non autorisé.');
        }

        // Vérifier que l'utilisateur est admin ou super admin
        if (!$user->isSuperAdmin() && !$user->hasRoleInCompany('admin', $company->id)) {
            abort(403, 'Seuls les administrateurs peuvent modifier des invitations.');
        }

        // Les invitations expirées ne peuvent pas être modifiées
        if ($invitation->isExpired()) {
            return redirect()->route('invitations.index', $company)
                ->with('error', 'Cette invitation a expiré et ne peut plus être modifiée.');
        }

        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'role_id' => 'required|integer|exists:roles,id',
            'project_id' => 'nullable|integer|exists:projects,id',
            'message' => 'nullable|string|max:1000',
        ]);

        // Vérifier que le projet appartient bien à l'entreprise si un projet est sélectionné
        if (isset($validated['project_id']) && $validated['project_id']) {
            $project = \App\Models\Project::find($validated['project_id']);
            if (!$project || $project->company_id != $company->id) {
                return redirect()->back()
                    ->withErrors(['project_id' => 'Le projet sélectionné n\'appartient pas à cette entreprise.'])
                    ->withInput();
            }
        }
        
        // Convertir role_id en entier pour éviter les problèmes de type
        $validated['role_id'] = (int) $validated['role_id'];
        
        // Vérifier que le rôle existe vraiment
        $role = Role::find($validated['role_id']);
        if (!$role) {
            return redirect()->back()
                ->withErrors(['role_id' => 'Le rôle sélectionné n\'existe pas.'])
                ->withInput();
        }

        // Vérifier si l'email a changé et s'il existe déjà une invitation en attente pour ce nouvel email
        if ($validated['email'] !== $invitation->email) {
            $existingInvitation = Invitation::where('company_id', $company->id)
                ->where('email', $validated['email'])
                ->where('status', 'pending')
                ->where('expires_at', '>', now())
                ->where('id', '!=', $invitation->id)
                ->first();

            if ($existingInvitation) {
                return redirect()->back()
                    ->with('error', 'Une invitation est déjà en attente pour cet email.')
                    ->withInput();
            }
        }

        $oldProjectId = $invitation->project_id;
        $invitation->update($validated);

        // Si l'invitation a été acceptée, mettre à jour l'association projet de l'utilisateur
        if ($invitation->status === 'accepted') {
            $invitedUser = User::where('email', $invitation->email)->first();
            if ($invitedUser) {
                // Retirer l'ancien projet si nécessaire
                if ($oldProjectId) {
                    $oldProject = \App\Models\Project::find($oldProjectId);
                    if ($oldProject) {
                        $oldProject->users()->detach($invitedUser->id);
                    }
                }
                
                // Ajouter le nouveau projet si un projet est spécifié
                if ($validated['project_id']) {
                    $newProject = \App\Models\Project::find($validated['project_id']);
                    if ($newProject && !$newProject->users()->where('users.id', $invitedUser->id)->exists()) {
                        $newProject->users()->attach($invitedUser->id);
                    }
                }
            }
        }

        return redirect()->route('invitations.index', $company)
            ->with('success', 'Invitation modifiée avec succès.');
    }

    /**
     * Supprimer une invitation
     */
    public function destroy(Company $company, Invitation $invitation)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        // Super admin peut accéder à toutes les entreprises
        $canAccess = $user->canAccessCompanyResource($company->id);
        $invitationMatches = (int) $invitation->company_id === (int) $company->id;
        
        if (!$canAccess || !$invitationMatches) {
            \Log::warning('Accès refusé - destroy', [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'invitation_company_id' => $invitation->company_id,
                'can_access' => $canAccess,
                'invitation_matches' => $invitationMatches,
            ]);
            abort(403, 'Accès non autorisé.');
        }

        // Vérifier que l'utilisateur est admin ou super admin
        if (!$user->isSuperAdmin() && !$user->hasRoleInCompany('admin', $company->id)) {
            abort(403, 'Seuls les administrateurs peuvent supprimer des invitations.');
        }

        // Supprimer réellement l'invitation de la base de données
        $invitation->delete();

        return redirect()->route('invitations.index', $company)
            ->with('success', 'Invitation supprimée avec succès.');
    }

    /**
     * Renvoyer une invitation
     */
    public function resend(Company $company, Invitation $invitation)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        // Super admin peut accéder à toutes les entreprises
        $canAccess = $user->canAccessCompanyResource($company->id);
        $invitationMatches = (int) $invitation->company_id === (int) $company->id;
        
        if (!$canAccess || !$invitationMatches) {
            \Log::warning('Accès refusé - resend', [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'invitation_company_id' => $invitation->company_id,
                'can_access' => $canAccess,
                'invitation_matches' => $invitationMatches,
            ]);
            abort(403, 'Accès non autorisé.');
        }

        // Vérifier que l'utilisateur est admin ou super admin
        if (!$user->isSuperAdmin() && !$user->hasRoleInCompany('admin', $company->id)) {
            abort(403, 'Seuls les administrateurs peuvent renvoyer des invitations.');
        }

        if ($invitation->status !== 'pending') {
            return redirect()->back()
                ->with('error', 'Seules les invitations en attente peuvent être renvoyées.');
        }

        // Régénérer le token et prolonger la validité
        $invitation->token = Invitation::generateToken();
        $invitation->expires_at = now()->addDays(7);
        $invitation->save();

        // Renvoyer l'email
        try {
            Mail::to($invitation->email)->send(new InvitationMail($invitation));
        } catch (\Exception $e) {
            \Log::error('Erreur envoi email invitation: ' . $e->getMessage());
        }

        return redirect()->back()
            ->with('success', 'Invitation renvoyée avec succès.');
    }
}
