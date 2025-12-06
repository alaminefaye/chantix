<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Project;
use App\Models\Material;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ExpenseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Project $project)
    {
        $user = Auth::user();
        $projectCompanyId = $project->company_id;

        // Vérifier les permissions : Super admin ou utilisateur de l'entreprise
        if (!$user->isSuperAdmin()) {
            // Vérifier que l'utilisateur appartient à l'entreprise du projet
            if (!$user->companies()->where('companies.id', $projectCompanyId)->exists()) {
                abort(403, 'Vous n\'appartenez pas à l\'entreprise de ce projet.');
            }
        }

        $type = request()->get('type');
        $query = $project->expenses()->with('creator', 'material', 'employee');

        if ($type) {
            $query->ofType($type);
        }

        $expenses = $query->orderBy('expense_date', 'desc')->paginate(20);

        // Statistiques
        $totalExpenses = $project->expenses()->sum('amount');
        $paidExpenses = $project->expenses()->paid()->sum('amount');
        $unpaidExpenses = $project->expenses()->unpaid()->sum('amount');
        $byType = $project->expenses()
            ->selectRaw('type, SUM(amount) as total')
            ->groupBy('type')
            ->get()
            ->pluck('total', 'type');

        return view('expenses.index', compact('project', 'expenses', 'totalExpenses', 'paidExpenses', 'unpaidExpenses', 'byType', 'type'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Project $project)
    {
        $user = Auth::user();
        $projectCompanyId = $project->company_id;

        // Vérifier les permissions : Super admin ou utilisateur de l'entreprise
        if (!$user->isSuperAdmin()) {
            // Vérifier que l'utilisateur appartient à l'entreprise du projet
            if (!$user->companies()->where('companies.id', $projectCompanyId)->exists()) {
                abort(403, 'Vous n\'appartenez pas à l\'entreprise de ce projet.');
            }
        }

        $materials = Material::forCompany($projectCompanyId)->active()->get();
        $employees = Employee::forCompany($projectCompanyId)->active()->get();

        return view('expenses.create', compact('project', 'materials', 'employees'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Project $project)
    {
        $user = Auth::user();
        $projectCompanyId = $project->company_id;

        // Vérifier les permissions : Super admin ou utilisateur de l'entreprise
        if (!$user->isSuperAdmin()) {
            // Vérifier que l'utilisateur appartient à l'entreprise du projet
            if (!$user->companies()->where('companies.id', $projectCompanyId)->exists()) {
                abort(403, 'Vous n\'appartenez pas à l\'entreprise de ce projet.');
            }
        }

        $validated = $request->validate([
            'type' => 'required|in:materiaux,transport,main_oeuvre,location,autres',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'supplier' => 'nullable|string|max:255',
            'invoice_number' => 'nullable|string|max:255',
            'invoice_date' => 'nullable|date',
            'invoice_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'material_id' => 'nullable|exists:materials,id',
            'employee_id' => 'nullable|exists:employees,id',
            'notes' => 'nullable|string',
            'is_paid' => 'nullable',
            'paid_date' => 'nullable|date',
        ]);

        // Vérifier que le matériau ou l'employé appartient à la même entreprise que le projet
        if (isset($validated['material_id']) && $validated['material_id']) {
            $material = Material::findOrFail($validated['material_id']);
            if ($material->company_id !== $projectCompanyId) {
                return redirect()->back()
                    ->with('error', 'Le matériau sélectionné n\'appartient pas à l\'entreprise du projet.')
                    ->withInput();
            }
        }

        if (isset($validated['employee_id']) && $validated['employee_id']) {
            $employee = Employee::findOrFail($validated['employee_id']);
            if ($employee->company_id !== $projectCompanyId) {
                return redirect()->back()
                    ->with('error', 'L\'employé sélectionné n\'appartient pas à l\'entreprise du projet.')
                    ->withInput();
            }
        }

        // Upload de la facture
        if ($request->hasFile('invoice_file')) {
            $file = $request->file('invoice_file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('expenses/invoices', $filename, 'public');
            $validated['invoice_file'] = $path;
        }

        try {
            $validated['project_id'] = $project->id;
            $validated['created_by'] = $user->id;
            
            // Gérer is_paid : checkbox non cochée = false, cochée = true
            $validated['is_paid'] = $request->has('is_paid') && ($request->input('is_paid') == '1' || $request->input('is_paid') === true);
            
            // Si is_paid est false, on ne doit pas avoir de paid_date
            if (!$validated['is_paid']) {
                $validated['paid_date'] = null;
            } elseif ($validated['is_paid'] && empty($validated['paid_date'])) {
                // Si is_paid est true mais pas de date, utiliser la date actuelle
                $validated['paid_date'] = now()->format('Y-m-d');
            }

            Expense::create($validated);

            return redirect()->route('expenses.index', $project)
                ->with('success', 'Dépense créée avec succès.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erreur lors de la création de la dépense: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project, $expense)
    {
        $user = Auth::user();
        $projectCompanyId = $project->company_id;

        // Récupérer la dépense manuellement si c'est un ID
        if (is_numeric($expense)) {
            $expense = Expense::where('id', $expense)
                ->where('project_id', $project->id)
                ->firstOrFail();
        } elseif (!$expense instanceof Expense) {
            abort(404, 'Dépense non trouvée.');
        }

        // Vérifier que la dépense appartient au projet
        if ($expense->project_id !== $project->id) {
            abort(404, 'Dépense non trouvée dans ce projet.');
        }

        // Vérifier les permissions : Super admin ou utilisateur de l'entreprise
        if (!$user->isSuperAdmin()) {
            // Vérifier que l'utilisateur appartient à l'entreprise du projet
            if (!$user->companies()->where('companies.id', $projectCompanyId)->exists()) {
                abort(403, 'Vous n\'appartenez pas à l\'entreprise de ce projet.');
            }
        }

        $expense->load('creator', 'material', 'employee', 'project');

        return view('expenses.show', compact('project', 'expense'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Project $project, $expense)
    {
        $user = Auth::user();
        $projectCompanyId = $project->company_id;

        // Récupérer la dépense manuellement si c'est un ID
        if (is_numeric($expense)) {
            $expense = Expense::where('id', $expense)
                ->where('project_id', $project->id)
                ->firstOrFail();
        } elseif (!$expense instanceof Expense) {
            abort(404, 'Dépense non trouvée.');
        }

        // Vérifier que la dépense appartient au projet
        if ($expense->project_id !== $project->id) {
            abort(404, 'Dépense non trouvée dans ce projet.');
        }

        // Vérifier les permissions : Super admin ou utilisateur de l'entreprise
        if (!$user->isSuperAdmin()) {
            // Vérifier que l'utilisateur appartient à l'entreprise du projet
            if (!$user->companies()->where('companies.id', $projectCompanyId)->exists()) {
                abort(403, 'Vous n\'appartenez pas à l\'entreprise de ce projet.');
            }
        }

        $materials = Material::forCompany($projectCompanyId)->active()->get();
        $employees = Employee::forCompany($projectCompanyId)->active()->get();

        return view('expenses.edit', compact('project', 'expense', 'materials', 'employees'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Project $project, $expense)
    {
        $user = Auth::user();
        $projectCompanyId = $project->company_id;

        // Récupérer la dépense manuellement si c'est un ID
        if (is_numeric($expense)) {
            $expense = Expense::where('id', $expense)
                ->where('project_id', $project->id)
                ->firstOrFail();
        } elseif (!$expense instanceof Expense) {
            abort(404, 'Dépense non trouvée.');
        }

        // Vérifier que la dépense appartient au projet
        if ($expense->project_id !== $project->id) {
            abort(404, 'Dépense non trouvée dans ce projet.');
        }

        // Vérifier les permissions : Super admin ou utilisateur de l'entreprise
        if (!$user->isSuperAdmin()) {
            // Vérifier que l'utilisateur appartient à l'entreprise du projet
            if (!$user->companies()->where('companies.id', $projectCompanyId)->exists()) {
                abort(403, 'Vous n\'appartenez pas à l\'entreprise de ce projet.');
            }
        }

        $validated = $request->validate([
            'type' => 'required|in:materiaux,transport,main_oeuvre,location,autres',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'supplier' => 'nullable|string|max:255',
            'invoice_number' => 'nullable|string|max:255',
            'invoice_date' => 'nullable|date',
            'invoice_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'material_id' => 'nullable|exists:materials,id',
            'employee_id' => 'nullable|exists:employees,id',
            'notes' => 'nullable|string',
            'is_paid' => 'nullable',
            'paid_date' => 'nullable|date',
        ]);

        // Vérifier que le matériau ou l'employé appartient à la même entreprise que le projet
        if (isset($validated['material_id']) && $validated['material_id']) {
            $material = Material::findOrFail($validated['material_id']);
            if ($material->company_id !== $projectCompanyId) {
                return redirect()->back()
                    ->with('error', 'Le matériau sélectionné n\'appartient pas à l\'entreprise du projet.')
                    ->withInput();
            }
        }

        if (isset($validated['employee_id']) && $validated['employee_id']) {
            $employee = Employee::findOrFail($validated['employee_id']);
            if ($employee->company_id !== $projectCompanyId) {
                return redirect()->back()
                    ->with('error', 'L\'employé sélectionné n\'appartient pas à l\'entreprise du projet.')
                    ->withInput();
            }
        }

        // Upload de la nouvelle facture
        if ($request->hasFile('invoice_file')) {
            // Supprimer l'ancienne facture si elle existe
            if ($expense->invoice_file) {
                Storage::disk('public')->delete($expense->invoice_file);
            }

            $file = $request->file('invoice_file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('expenses/invoices', $filename, 'public');
            $validated['invoice_file'] = $path;
        } else {
            // Garder l'ancienne facture
            unset($validated['invoice_file']);
        }

        // Gérer is_paid : checkbox non cochée = false, cochée = true
        $validated['is_paid'] = $request->has('is_paid') && ($request->input('is_paid') == '1' || $request->input('is_paid') === true);
        
        // Si is_paid est false, on ne doit pas avoir de paid_date
        if (!$validated['is_paid']) {
            $validated['paid_date'] = null;
        } elseif ($validated['is_paid'] && empty($validated['paid_date'])) {
            // Si is_paid est true mais pas de date, utiliser la date actuelle
            $validated['paid_date'] = now()->format('Y-m-d');
        }

        $expense->update($validated);

        return redirect()->route('expenses.index', $project)
            ->with('success', 'Dépense mise à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project, $expense)
    {
        $user = Auth::user();
        $projectCompanyId = $project->company_id;

        // Récupérer la dépense manuellement si c'est un ID
        if (is_numeric($expense)) {
            $expense = Expense::where('id', $expense)
                ->where('project_id', $project->id)
                ->firstOrFail();
        } elseif (!$expense instanceof Expense) {
            abort(404, 'Dépense non trouvée.');
        }

        // Vérifier que la dépense appartient au projet
        if ($expense->project_id !== $project->id) {
            abort(404, 'Dépense non trouvée dans ce projet.');
        }

        // Vérifier les permissions : Super admin ou utilisateur de l'entreprise
        if (!$user->isSuperAdmin()) {
            // Vérifier que l'utilisateur appartient à l'entreprise du projet
            if (!$user->companies()->where('companies.id', $projectCompanyId)->exists()) {
                abort(403, 'Vous n\'appartenez pas à l\'entreprise de ce projet.');
            }
        }

        // Supprimer la facture si elle existe
        if ($expense->invoice_file) {
            Storage::disk('public')->delete($expense->invoice_file);
        }

        $expense->delete();

        return redirect()->route('expenses.index', $project)
            ->with('success', 'Dépense supprimée avec succès.');
    }
}
