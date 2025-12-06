<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Project;
use App\Models\Material;
use App\Models\Employee;
use App\Services\PushNotificationService;
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
            // Un checkbox envoie "1" (ou "on" par défaut) quand coché, rien quand non coché
            $isPaid = $request->has('is_paid') && (
                $request->input('is_paid') == '1' || 
                $request->input('is_paid') === 'on' || 
                $request->input('is_paid') === true ||
                $request->input('is_paid') === 'true'
            );
            $validated['is_paid'] = $isPaid;
            
            // Si is_paid est false, on ne doit pas avoir de paid_date
            if (!$validated['is_paid']) {
                $validated['paid_date'] = null;
            } elseif ($validated['is_paid'] && empty($validated['paid_date'])) {
                // Si is_paid est true mais pas de date, utiliser la date actuelle
                $validated['paid_date'] = now()->format('Y-m-d');
            }

            $expense = Expense::create($validated);

            // Envoyer des notifications push aux utilisateurs concernés
            try {
                $pushService = new PushNotificationService();
                $amount = number_format($validated['amount'], 0, ',', ' ') . ' FCFA';
                $pushService->notifyProjectStakeholders(
                    $project,
                    'expense_created',
                    'Nouvelle dépense créée',
                    "Une nouvelle dépense a été créée pour le projet \"{$project->name}\" : {$validated['title']} ({$amount})",
                    [
                        'expense_id' => $expense->id,
                        'expense_title' => $validated['title'],
                        'expense_amount' => $validated['amount'],
                        'expense_type' => $validated['type'],
                    ],
                    $user->id // Exclure l'utilisateur qui a créé la dépense
                );
            } catch (\Exception $e) {
                // Ne pas faire échouer la création de la dépense si l'envoi de notification échoue
                \Log::warning("Failed to send expense creation notification: " . $e->getMessage());
            }

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

        // Récupérer la dépense via la relation du projet pour s'assurer qu'elle appartient au projet
        // Cela garantit que la dépense appartient bien au projet spécifié
        $expense = $project->expenses()->findOrFail($expense);

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

        // Récupérer la dépense via la relation du projet pour s'assurer qu'elle appartient au projet
        // Cela garantit que la dépense appartient bien au projet spécifié
        $expense = $project->expenses()->findOrFail($expense);

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

        // Récupérer la dépense via la relation du projet pour s'assurer qu'elle appartient au projet
        // Cela garantit que la dépense appartient bien au projet spécifié
        $expense = $project->expenses()->findOrFail($expense);

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
        // Un checkbox envoie "1" (ou "on" par défaut) quand coché, rien quand non coché
        $isPaid = $request->has('is_paid') && (
            $request->input('is_paid') == '1' || 
            $request->input('is_paid') === 'on' || 
            $request->input('is_paid') === true ||
            $request->input('is_paid') === 'true'
        );
        $validated['is_paid'] = $isPaid;
        
        // Si is_paid est false, on ne doit pas avoir de paid_date
        if (!$validated['is_paid']) {
            $validated['paid_date'] = null;
        } elseif ($validated['is_paid'] && empty($validated['paid_date'])) {
            // Si is_paid est true mais pas de date, utiliser la date actuelle
            $validated['paid_date'] = now()->format('Y-m-d');
        }

        $expense->update($validated);

        // Recharger le projet pour avoir les données à jour
        $expense->load('project');

        // Envoyer des notifications push aux utilisateurs concernés
        try {
            $pushService = new PushNotificationService();
            $amount = number_format($validated['amount'], 0, ',', ' ') . ' FCFA';
            $pushService->notifyProjectStakeholders(
                $expense->project,
                'expense_updated',
                'Dépense modifiée',
                "Une dépense a été modifiée pour le projet \"{$expense->project->name}\" : {$validated['title']} ({$amount})",
                [
                    'expense_id' => $expense->id,
                    'expense_title' => $validated['title'],
                    'expense_amount' => $validated['amount'],
                    'expense_type' => $validated['type'],
                ],
                $user->id // Exclure l'utilisateur qui a modifié la dépense
            );
        } catch (\Exception $e) {
            // Ne pas faire échouer la modification de la dépense si l'envoi de notification échoue
            \Log::warning("Failed to send expense update notification: " . $e->getMessage());
        }

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

        // Récupérer la dépense via la relation du projet pour s'assurer qu'elle appartient au projet
        // Cela garantit que la dépense appartient bien au projet spécifié
        $expense = $project->expenses()->findOrFail($expense);

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
