<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Project;
use App\Models\Material;
use App\Models\Employee;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ExpenseController extends Controller
{
    /**
     * Liste des dépenses
     */
    public function index(Request $request, $projectId = null)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if (!$companyId && !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez sélectionner une entreprise.',
            ], 400);
        }

        $query = Expense::query();

        // Filtrer par projet si fourni
        if ($projectId) {
            $project = Project::find($projectId);
            if (!$project) {
                return response()->json([
                    'success' => false,
                    'message' => 'Projet non trouvé.',
                ], 404);
            }

            if ($project->company_id !== $companyId && !$user->isSuperAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé.',
                ], 403);
            }

            $query->where('project_id', $projectId);
        } else {
            // Filtrer par entreprise
            if ($companyId) {
                $projectIds = Project::forCompany($companyId)->pluck('id');
                $query->whereIn('project_id', $projectIds);
            }
        }

        // Filtre par type
        if ($request->filled('type')) {
            $query->ofType($request->type);
        }

        // Filtre par statut payé
        if ($request->filled('is_paid')) {
            $query->where('is_paid', $request->is_paid);
        }

        // Recherche
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%')
                  ->orWhere('supplier', 'like', '%' . $request->search . '%');
            });
        }

        // Tri
        $sortBy = $request->get('sort_by', 'expense_date');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $expenses = $query->with(['project', 'creator', 'material', 'employee'])->get();

        return response()->json([
            'success' => true,
            'data' => $expenses,
        ], 200);
    }

    /**
     * Détails d'une dépense
     */
    public function show($id, $projectId = null)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if (!$companyId && !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez sélectionner une entreprise.',
            ], 400);
        }

        $expense = Expense::with(['project', 'creator', 'material', 'employee'])->find($id);

        if (!$expense) {
            return response()->json([
                'success' => false,
                'message' => 'Dépense non trouvée.',
            ], 404);
        }

        // Vérifier l'accès
        if ($expense->project->company_id !== $companyId && !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        return response()->json($expense, 200);
    }

    /**
     * Créer une dépense
     */
    public function store(Request $request, $projectId)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if (!$companyId && !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez sélectionner une entreprise.',
            ], 400);
        }

        $project = Project::find($projectId);
        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Projet non trouvé.',
            ], 404);
        }

        if ($project->company_id !== $companyId && !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
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
            'is_paid' => 'nullable|boolean',
            'paid_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['project_id'] = $projectId;
        $data['created_by'] = $user->id;
        $data['is_paid'] = $request->input('is_paid', false);

        // Vérifier que le matériau ou l'employé appartient à la même entreprise
        if (isset($data['material_id']) && $data['material_id']) {
            $material = Material::find($data['material_id']);
            if (!$material || $material->company_id !== $companyId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le matériau sélectionné n\'appartient pas à votre entreprise.',
                ], 422);
            }
        }

        if (isset($data['employee_id']) && $data['employee_id']) {
            $employee = Employee::find($data['employee_id']);
            if (!$employee || $employee->company_id !== $companyId) {
                return response()->json([
                    'success' => false,
                    'message' => 'L\'employé sélectionné n\'appartient pas à votre entreprise.',
                ], 422);
            }
        }

        // Upload de la facture
        if ($request->hasFile('invoice_file')) {
            $file = $request->file('invoice_file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('expenses/invoices', $filename, 'public');
            $data['invoice_file'] = $path;
        }

        $expense = Expense::create($data);

        // Envoyer des notifications push aux utilisateurs concernés
        try {
            $pushService = new PushNotificationService();
            $amount = number_format($data['amount'], 0, ',', ' ') . ' FCFA';
            $pushService->notifyProjectStakeholders(
                $project,
                'expense_created',
                'Nouvelle dépense créée',
                "Une nouvelle dépense a été créée pour le projet \"{$project->name}\" : {$data['title']} ({$amount})",
                [
                    'expense_id' => $expense->id,
                    'expense_title' => $data['title'],
                    'expense_amount' => $data['amount'],
                    'expense_type' => $data['type'],
                ],
                $user->id // Exclure l'utilisateur qui a créé la dépense
            );
        } catch (\Exception $e) {
            // Ne pas faire échouer la création de la dépense si l'envoi de notification échoue
            \Log::warning("Failed to send expense creation notification: " . $e->getMessage());
        }

        return response()->json($expense->load(['project', 'creator', 'material', 'employee']), 201);
    }

    /**
     * Mettre à jour une dépense
     */
    public function update(Request $request, $id, $projectId = null)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if (!$companyId && !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez sélectionner une entreprise.',
            ], 400);
        }

        $expense = Expense::find($id);
        if (!$expense) {
            return response()->json([
                'success' => false,
                'message' => 'Dépense non trouvée.',
            ], 404);
        }

        if ($expense->project->company_id !== $companyId && !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'type' => 'sometimes|required|in:materiaux,transport,main_oeuvre,location,autres',
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'sometimes|required|numeric|min:0',
            'expense_date' => 'sometimes|required|date',
            'supplier' => 'nullable|string|max:255',
            'invoice_number' => 'nullable|string|max:255',
            'invoice_date' => 'nullable|date',
            'invoice_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'material_id' => 'nullable|exists:materials,id',
            'employee_id' => 'nullable|exists:employees,id',
            'notes' => 'nullable|string',
            'is_paid' => 'nullable|boolean',
            'paid_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Upload de la nouvelle facture
        if ($request->hasFile('invoice_file')) {
            // Supprimer l'ancienne facture
            if ($expense->invoice_file) {
                Storage::disk('public')->delete($expense->invoice_file);
            }

            $file = $request->file('invoice_file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('expenses/invoices', $filename, 'public');
            $data['invoice_file'] = $path;
        }

        $expense->update($data);

        // Envoyer des notifications push aux utilisateurs concernés
        try {
            $pushService = new PushNotificationService();
            $amount = number_format($data['amount'], 0, ',', ' ') . ' FCFA';
            $pushService->notifyProjectStakeholders(
                $expense->project,
                'expense_updated',
                'Dépense modifiée',
                "Une dépense a été modifiée pour le projet \"{$expense->project->name}\" : {$data['title']} ({$amount})",
                [
                    'expense_id' => $expense->id,
                    'expense_title' => $data['title'],
                    'expense_amount' => $data['amount'],
                    'expense_type' => $data['type'],
                ],
                $user->id // Exclure l'utilisateur qui a modifié la dépense
            );
        } catch (\Exception $e) {
            // Ne pas faire échouer la modification de la dépense si l'envoi de notification échoue
            \Log::warning("Failed to send expense update notification: " . $e->getMessage());
        }

        return response()->json($expense->load(['project', 'creator', 'material', 'employee']), 200);
    }

    /**
     * Supprimer une dépense
     */
    public function destroy($id, $projectId = null)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if (!$companyId && !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez sélectionner une entreprise.',
            ], 400);
        }

        $expense = Expense::find($id);
        if (!$expense) {
            return response()->json([
                'success' => false,
                'message' => 'Dépense non trouvée.',
            ], 404);
        }

        if ($expense->project->company_id !== $companyId && !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        // Supprimer le fichier de facture
        if ($expense->invoice_file) {
            Storage::disk('public')->delete($expense->invoice_file);
        }

        $expense->delete();

        return response()->json([
            'success' => true,
            'message' => 'Dépense supprimée avec succès.',
        ], 200);
    }
}

