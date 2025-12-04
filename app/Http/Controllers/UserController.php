<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use App\Models\Role as OldRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Afficher les utilisateurs d'une entreprise
     */
    public function index(Company $company)
    {
        $user = Auth::user();

        // Vérifier les permissions
        if (!$user->canAccessCompanyResource($company->id)) {
            abort(403, 'Accès non autorisé.');
        }

        if (!$user->isSuperAdmin() && !$user->can('users.view')) {
            abort(403, 'Vous n\'avez pas la permission de voir les utilisateurs.');
        }

        $users = $company->users()
            ->withPivot('role_id', 'is_active', 'joined_at')
            ->orderBy('name')
            ->paginate(20);

        // Charger les rôles Spatie pour chaque utilisateur
        foreach ($users as $userItem) {
            $pivotRoleId = $userItem->pivot->role_id;
            if ($pivotRoleId) {
                $role = Role::find($pivotRoleId);
                $userItem->companyRole = $role;
            }
        }

        $roles = OldRole::all();

        return view('users.index', compact('company', 'users', 'roles'));
    }

    /**
     * Afficher le formulaire de création d'utilisateur
     */
    public function create(Company $company)
    {
        $user = Auth::user();

        if (!$user->canAccessCompanyResource($company->id)) {
            abort(403, 'Accès non autorisé.');
        }

        if (!$user->isSuperAdmin() && !$user->can('users.create')) {
            abort(403, 'Vous n\'avez pas la permission de créer des utilisateurs.');
        }

        $roles = OldRole::all();

        return view('users.create', compact('company', 'roles'));
    }

    /**
     * Créer un nouvel utilisateur
     */
    public function store(Request $request, Company $company)
    {
        $user = Auth::user();

        if (!$user->canAccessCompanyResource($company->id)) {
            abort(403, 'Accès non autorisé.');
        }

        if (!$user->isSuperAdmin() && !$user->can('users.create')) {
            abort(403, 'Vous n\'avez pas la permission de créer des utilisateurs.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'role_id' => 'required|exists:roles,id',
        ]);

        // Vérifier si l'utilisateur existe déjà dans l'entreprise
        $existingUser = User::where('email', $validated['email'])->first();
        
        if ($existingUser && $company->users()->where('users.id', $existingUser->id)->exists()) {
            return redirect()->back()
                ->with('error', 'Cet utilisateur fait déjà partie de l\'entreprise.')
                ->withInput();
        }

        if ($existingUser) {
            // Ajouter l'utilisateur existant à l'entreprise
            $company->users()->attach($existingUser->id, [
                'role_id' => $validated['role_id'],
                'is_active' => true,
                'joined_at' => now(),
            ]);

            // Assigner le rôle Spatie
            $oldRole = OldRole::find($validated['role_id']);
            if ($oldRole) {
                $spatieRole = Role::where('name', $oldRole->name)->first();
                if ($spatieRole) {
                    $existingUser->assignRole($spatieRole);
                }
            }

            return redirect()->route('users.index', $company)
                ->with('success', 'Utilisateur ajouté à l\'entreprise avec succès.');
        }

        // Créer un nouvel utilisateur
        $newUser = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_verified' => true, // Les utilisateurs créés directement sont vérifiés
        ]);

        // Ajouter l'utilisateur à l'entreprise
        $company->users()->attach($newUser->id, [
            'role_id' => $validated['role_id'],
            'is_active' => true,
            'joined_at' => now(),
        ]);

        // Assigner le rôle Spatie
        $oldRole = OldRole::find($validated['role_id']);
        if ($oldRole) {
            $spatieRole = Role::where('name', $oldRole->name)->first();
            if ($spatieRole) {
                $newUser->assignRole($spatieRole);
            }
        }

        // Définir l'entreprise comme actuelle si l'utilisateur n'en a pas
        if (!$newUser->current_company_id) {
            $newUser->current_company_id = $company->id;
            $newUser->save();
        }

        return redirect()->route('users.index', $company)
            ->with('success', 'Utilisateur créé et ajouté à l\'entreprise avec succès.');
    }

    /**
     * Afficher les détails d'un utilisateur
     */
    public function show(Company $company, User $user)
    {
        $authUser = Auth::user();

        if (!$authUser->canAccessCompanyResource($company->id)) {
            abort(403, 'Accès non autorisé.');
        }

        if (!$authUser->isSuperAdmin() && !$authUser->can('users.view')) {
            abort(403, 'Vous n\'avez pas la permission de voir les utilisateurs.');
        }

        // Vérifier que l'utilisateur appartient à l'entreprise
        if (!$company->users()->where('users.id', $user->id)->exists()) {
            abort(404, 'Utilisateur non trouvé dans cette entreprise.');
        }

        $pivot = $company->users()->where('users.id', $user->id)->first()->pivot;
        $role = OldRole::find($pivot->role_id);

        return view('users.show', compact('company', 'user', 'role', 'pivot'));
    }

    /**
     * Afficher le formulaire de modification d'un utilisateur
     */
    public function edit(Company $company, User $user)
    {
        $authUser = Auth::user();

        if (!$authUser->canAccessCompanyResource($company->id)) {
            abort(403, 'Accès non autorisé.');
        }

        if (!$authUser->isSuperAdmin() && !$authUser->can('users.update')) {
            abort(403, 'Vous n\'avez pas la permission de modifier des utilisateurs.');
        }

        // Vérifier que l'utilisateur appartient à l'entreprise
        if (!$company->users()->where('users.id', $user->id)->exists()) {
            abort(404, 'Utilisateur non trouvé dans cette entreprise.');
        }

        $pivot = $company->users()->where('users.id', $user->id)->first()->pivot;
        $currentRole = OldRole::find($pivot->role_id);
        $roles = OldRole::all();

        return view('users.edit', compact('company', 'user', 'currentRole', 'roles', 'pivot'));
    }

    /**
     * Mettre à jour un utilisateur
     */
    public function update(Request $request, Company $company, User $user)
    {
        $authUser = Auth::user();

        if (!$authUser->canAccessCompanyResource($company->id)) {
            abort(403, 'Accès non autorisé.');
        }

        if (!$authUser->isSuperAdmin() && !$authUser->can('users.update')) {
            abort(403, 'Vous n\'avez pas la permission de modifier des utilisateurs.');
        }

        // Vérifier que l'utilisateur appartient à l'entreprise
        if (!$company->users()->where('users.id', $user->id)->exists()) {
            abort(404, 'Utilisateur non trouvé dans cette entreprise.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8',
            'role_id' => 'required|exists:roles,id',
            'is_active' => 'nullable|boolean',
        ]);

        // Mettre à jour l'utilisateur
        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'] ? Hash::make($validated['password']) : $user->password,
        ]);

        // Mettre à jour le rôle dans la table pivot
        $company->users()->updateExistingPivot($user->id, [
            'role_id' => $validated['role_id'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        // Mettre à jour le rôle Spatie
        $oldRole = OldRole::find($validated['role_id']);
        if ($oldRole) {
            $spatieRole = Role::where('name', $oldRole->name)->first();
            if ($spatieRole) {
                $user->syncRoles([$spatieRole]);
            }
        }

        return redirect()->route('users.index', $company)
            ->with('success', 'Utilisateur modifié avec succès.');
    }

    /**
     * Supprimer un utilisateur de l'entreprise
     */
    public function destroy(Company $company, User $user)
    {
        $authUser = Auth::user();

        if (!$authUser->canAccessCompanyResource($company->id)) {
            abort(403, 'Accès non autorisé.');
        }

        if (!$authUser->isSuperAdmin() && !$authUser->can('users.delete')) {
            abort(403, 'Vous n\'avez pas la permission de supprimer des utilisateurs.');
        }

        // Vérifier que l'utilisateur appartient à l'entreprise
        if (!$company->users()->where('users.id', $user->id)->exists()) {
            abort(404, 'Utilisateur non trouvé dans cette entreprise.');
        }

        // Ne pas permettre de se supprimer soi-même
        if ($user->id === $authUser->id) {
            return redirect()->back()
                ->with('error', 'Vous ne pouvez pas vous supprimer vous-même.');
        }

        // Retirer l'utilisateur de l'entreprise
        $company->users()->detach($user->id);

        // Si c'était son entreprise actuelle, la réinitialiser
        if ($user->current_company_id === $company->id) {
            $user->current_company_id = null;
            $user->save();
        }

        return redirect()->route('users.index', $company)
            ->with('success', 'Utilisateur retiré de l\'entreprise avec succès.');
    }
}

