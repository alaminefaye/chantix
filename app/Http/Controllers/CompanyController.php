<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
{
    /**
     * Afficher la liste des entreprises de l'utilisateur
     */
    public function index()
    {
        $user = Auth::user();
        
        // Seul le super admin peut voir la gestion des entreprises
        if (!$user->isSuperAdmin()) {
            abort(403, 'Accès réservé au super administrateur.');
        }
        
        // Le super admin voit toutes les entreprises
        $companies = \App\Models\Company::where('is_active', true)->get();
        
        return view('companies.index', compact('companies'));
    }

    /**
     * Afficher le formulaire de création d'entreprise
     */
    public function create()
    {
        $user = Auth::user();
        
        // Seul le super admin peut créer des entreprises
        if (!$user->isSuperAdmin()) {
            abort(403, 'Seul le super administrateur peut créer des entreprises.');
        }
        
        return view('companies.create');
    }

    /**
     * Créer une nouvelle entreprise
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        // Seul le super admin peut créer des entreprises
        if (!$user->isSuperAdmin()) {
            abort(403, 'Seul le super administrateur peut créer des entreprises.');
        }
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'siret' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $company = Company::create($request->all());

        return redirect()->route('companies.index')
            ->with('success', 'Entreprise créée avec succès !');
    }

    /**
     * Changer l'entreprise actuelle
     */
    public function switch(Request $request, Company $company)
    {
        $user = Auth::user();
        
        // Le super admin peut accéder à toutes les entreprises
        if (!$user->isSuperAdmin()) {
            // Vérifier que l'utilisateur appartient à cette entreprise
            if (!$user->companies()->where('companies.id', $company->id)->exists()) {
                return back()->withErrors(['error' => 'Vous n\'appartenez pas à cette entreprise.']);
            }
        }

        $user->current_company_id = $company->id;
        $user->save();

        return redirect()->route('dashboard')->with('success', 'Entreprise changée avec succès !');
    }

    /**
     * Afficher les détails d'une entreprise
     */
    public function show(Company $company)
    {
        $user = Auth::user();
        
        // Seul le super admin peut voir les détails des entreprises
        if (!$user->isSuperAdmin()) {
            abort(403, 'Accès réservé au super administrateur.');
        }

        return view('companies.show', compact('company'));
    }

    /**
     * Afficher le formulaire d'édition
     */
    public function edit(Company $company)
    {
        $user = Auth::user();
        
        // Seul le super admin peut modifier les entreprises
        if (!$user->isSuperAdmin()) {
            abort(403, 'Accès réservé au super administrateur.');
        }

        return view('companies.edit', compact('company'));
    }

    /**
     * Mettre à jour une entreprise
     */
    public function update(Request $request, Company $company)
    {
        $user = Auth::user();
        
        // Seul le super admin peut modifier les entreprises
        if (!$user->isSuperAdmin()) {
            abort(403, 'Accès réservé au super administrateur.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'siret' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        // Upload du logo
        if ($request->hasFile('logo')) {
            // Supprimer l'ancien logo s'il existe
            if ($company->logo && Storage::disk('public')->exists($company->logo)) {
                Storage::disk('public')->delete($company->logo);
            }

            $path = $request->file('logo')->store('companies/logos', 'public');
            $validated['logo'] = $path;
        }

        $company->update($validated);

        return redirect()->route('companies.show', $company)
            ->with('success', 'Entreprise mise à jour avec succès !');
    }
}
