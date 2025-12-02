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
        $companyId = $user->current_company_id;

        if ($project->company_id !== $companyId) {
            abort(403, 'Accès non autorisé.');
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
        $companyId = $user->current_company_id;

        if ($project->company_id !== $companyId) {
            abort(403, 'Accès non autorisé.');
        }

        $materials = Material::forCompany($companyId)->active()->get();
        $employees = Employee::forCompany($companyId)->active()->get();

        return view('expenses.create', compact('project', 'materials', 'employees'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Project $project)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if ($project->company_id !== $companyId) {
            abort(403, 'Accès non autorisé.');
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
            'is_paid' => 'boolean',
            'paid_date' => 'nullable|date',
        ]);

        // Vérifier que le matériau ou l'employé appartient à la même entreprise
        if (isset($validated['material_id']) && $validated['material_id']) {
            $material = Material::findOrFail($validated['material_id']);
            if ($material->company_id !== $companyId) {
                return redirect()->back()
                    ->with('error', 'Le matériau sélectionné n\'appartient pas à votre entreprise.');
            }
        }

        if (isset($validated['employee_id']) && $validated['employee_id']) {
            $employee = Employee::findOrFail($validated['employee_id']);
            if ($employee->company_id !== $companyId) {
                return redirect()->back()
                    ->with('error', 'L\'employé sélectionné n\'appartient pas à votre entreprise.');
            }
        }

        // Upload de la facture
        if ($request->hasFile('invoice_file')) {
            $file = $request->file('invoice_file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('expenses/invoices', $filename, 'public');
            $validated['invoice_file'] = $path;
        }

        $validated['project_id'] = $project->id;
        $validated['created_by'] = $user->id;
        $validated['is_paid'] = $request->has('is_paid');

        Expense::create($validated);

        return redirect()->route('expenses.index', $project)
            ->with('success', 'Dépense créée avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project, Expense $expense)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if ($project->company_id !== $companyId || $expense->project_id !== $project->id) {
            abort(403, 'Accès non autorisé.');
        }

        $expense->load('creator', 'material', 'employee', 'project');

        return view('expenses.show', compact('project', 'expense'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Project $project, Expense $expense)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if ($project->company_id !== $companyId || $expense->project_id !== $project->id) {
            abort(403, 'Accès non autorisé.');
        }

        $materials = Material::forCompany($companyId)->active()->get();
        $employees = Employee::forCompany($companyId)->active()->get();

        return view('expenses.edit', compact('project', 'expense', 'materials', 'employees'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Project $project, Expense $expense)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if ($project->company_id !== $companyId || $expense->project_id !== $project->id) {
            abort(403, 'Accès non autorisé.');
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
            'is_paid' => 'boolean',
            'paid_date' => 'nullable|date',
        ]);

        // Vérifier que le matériau ou l'employé appartient à la même entreprise
        if (isset($validated['material_id']) && $validated['material_id']) {
            $material = Material::findOrFail($validated['material_id']);
            if ($material->company_id !== $companyId) {
                return redirect()->back()
                    ->with('error', 'Le matériau sélectionné n\'appartient pas à votre entreprise.');
            }
        }

        if (isset($validated['employee_id']) && $validated['employee_id']) {
            $employee = Employee::findOrFail($validated['employee_id']);
            if ($employee->company_id !== $companyId) {
                return redirect()->back()
                    ->with('error', 'L\'employé sélectionné n\'appartient pas à votre entreprise.');
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

        $validated['is_paid'] = $request->has('is_paid');

        $expense->update($validated);

        return redirect()->route('expenses.index', $project)
            ->with('success', 'Dépense mise à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project, Expense $expense)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if ($project->company_id !== $companyId || $expense->project_id !== $project->id) {
            abort(403, 'Accès non autorisé.');
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
