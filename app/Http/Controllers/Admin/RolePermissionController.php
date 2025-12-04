<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RolePermissionController extends Controller
{
    /**
     * Afficher la liste des rôles
     */
    public function index()
    {
        $user = Auth::user();
        
        // Seul le super admin peut accéder
        if (!$user->isSuperAdmin()) {
            abort(403, 'Accès réservé au super administrateur.');
        }

        $roles = Role::orderBy('name')->get();

        return view('admin.roles.index', compact('roles'));
    }

    /**
     * Afficher le formulaire de création de rôle
     */
    public function create()
    {
        $user = Auth::user();
        
        if (!$user->isSuperAdmin()) {
            abort(403, 'Accès réservé au super administrateur.');
        }

        // Liste des permissions disponibles
        $availablePermissions = $this->getAvailablePermissions();

        return view('admin.roles.create', compact('availablePermissions'));
    }

    /**
     * Créer un nouveau rôle
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->isSuperAdmin()) {
            abort(403, 'Accès réservé au super administrateur.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
        ]);

        $validated['permissions'] = $validated['permissions'] ?? [];

        Role::create($validated);

        return redirect()->route('admin.roles.index')
            ->with('success', 'Rôle créé avec succès.');
    }

    /**
     * Afficher les détails d'un rôle
     */
    public function show(Role $role)
    {
        $user = Auth::user();
        
        if (!$user->isSuperAdmin()) {
            abort(403, 'Accès réservé au super administrateur.');
        }

        $availablePermissions = $this->getAvailablePermissions();
        $role->load('users');

        return view('admin.roles.show', compact('role', 'availablePermissions'));
    }

    /**
     * Afficher le formulaire d'édition
     */
    public function edit(Role $role)
    {
        $user = Auth::user();
        
        if (!$user->isSuperAdmin()) {
            abort(403, 'Accès réservé au super administrateur.');
        }

        $availablePermissions = $this->getAvailablePermissions();

        return view('admin.roles.edit', compact('role', 'availablePermissions'));
    }

    /**
     * Mettre à jour un rôle
     */
    public function update(Request $request, Role $role)
    {
        $user = Auth::user();
        
        if (!$user->isSuperAdmin()) {
            abort(403, 'Accès réservé au super administrateur.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
        ]);

        $validated['permissions'] = $validated['permissions'] ?? [];

        $role->update($validated);

        return redirect()->route('admin.roles.index')
            ->with('success', 'Rôle mis à jour avec succès.');
    }

    /**
     * Supprimer un rôle
     */
    public function destroy(Role $role)
    {
        $user = Auth::user();
        
        if (!$user->isSuperAdmin()) {
            abort(403, 'Accès réservé au super administrateur.');
        }

        // Vérifier si le rôle est utilisé
        if ($role->companyUsers()->count() > 0) {
            return redirect()->route('admin.roles.index')
                ->with('error', 'Impossible de supprimer ce rôle car il est utilisé par des utilisateurs.');
        }

        $role->delete();

        return redirect()->route('admin.roles.index')
            ->with('success', 'Rôle supprimé avec succès.');
    }

    /**
     * Obtenir la liste des permissions disponibles
     */
    private function getAvailablePermissions()
    {
        return [
            'projects' => [
                'projects.view' => 'Voir les projets',
                'projects.create' => 'Créer des projets',
                'projects.edit' => 'Modifier des projets',
                'projects.delete' => 'Supprimer des projets',
                'projects.manage_team' => 'Gérer l\'équipe du projet',
            ],
            'materials' => [
                'materials.view' => 'Voir les matériaux',
                'materials.manage' => 'Gérer les matériaux',
            ],
            'employees' => [
                'employees.view' => 'Voir les employés',
                'employees.manage' => 'Gérer les employés',
            ],
            'attendance' => [
                'attendance.view' => 'Voir les pointages',
                'attendance.manage' => 'Gérer les pointages',
            ],
            'expenses' => [
                'expenses.view' => 'Voir les dépenses',
                'expenses.manage' => 'Gérer les dépenses',
            ],
            'reports' => [
                'reports.view' => 'Voir les rapports',
                'reports.generate' => 'Générer des rapports',
            ],
        ];
    }
}

