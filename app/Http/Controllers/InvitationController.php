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

        if ($company->id !== $companyId) {
            abort(403, 'Accès non autorisé.');
        }

        // Vérifier que l'utilisateur est admin
        if (!$user->hasRoleInCompany('admin', $companyId)) {
            abort(403, 'Seuls les administrateurs peuvent gérer les invitations.');
        }

        $invitations = $company->invitations()
            ->with('inviter', 'role')
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

        if ($company->id !== $companyId) {
            abort(403, 'Accès non autorisé.');
        }

        if (!$user->hasRoleInCompany('admin', $companyId)) {
            abort(403, 'Seuls les administrateurs peuvent inviter des utilisateurs.');
        }

        $roles = Role::all();

        return view('invitations.create', compact('company', 'roles'));
    }

    /**
     * Envoyer une invitation
     */
    public function store(Request $request, Company $company)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if ($company->id !== $companyId) {
            abort(403, 'Accès non autorisé.');
        }

        if (!$user->hasRoleInCompany('admin', $companyId)) {
            abort(403, 'Seuls les administrateurs peuvent inviter des utilisateurs.');
        }

        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'role_id' => 'required|exists:roles,id',
            'message' => 'nullable|string|max:1000',
            'create_directly' => 'nullable|boolean', // Option pour créer directement
            'name' => 'nullable|string|max:255|required_if:create_directly,1',
            'password' => 'nullable|string|min:8|required_if:create_directly,1',
        ]);

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
                    $company->users()->attach($existingUser->id, [
                        'role_id' => $validated['role_id'],
                        'is_active' => true,
                        'joined_at' => now(),
                    ]);

                    return redirect()->route('invitations.index', $company)
                        ->with('success', 'Utilisateur ajouté directement à l\'entreprise avec succès.');
                }
            } else {
                // Créer un nouvel utilisateur directement
                $newUser = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                ]);

                // Ajouter l'utilisateur à l'entreprise
                $company->users()->attach($newUser->id, [
                    'role_id' => $validated['role_id'],
                    'is_active' => true,
                    'joined_at' => now(),
                ]);

                // Créer une invitation marquée comme acceptée pour l'historique
                Invitation::create([
                    'company_id' => $company->id,
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
     * Annuler une invitation
     */
    public function destroy(Company $company, Invitation $invitation)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if ($company->id !== $companyId || $invitation->company_id !== $company->id) {
            abort(403, 'Accès non autorisé.');
        }

        if (!$user->hasRoleInCompany('admin', $companyId)) {
            abort(403, 'Seuls les administrateurs peuvent annuler des invitations.');
        }

        $invitation->markAsCancelled();

        return redirect()->route('invitations.index', $company)
            ->with('success', 'Invitation annulée avec succès.');
    }

    /**
     * Renvoyer une invitation
     */
    public function resend(Company $company, Invitation $invitation)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if ($company->id !== $companyId || $invitation->company_id !== $company->id) {
            abort(403, 'Accès non autorisé.');
        }

        if (!$user->hasRoleInCompany('admin', $companyId)) {
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
