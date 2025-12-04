<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class EmployeeController extends Controller
{
    /**
     * Liste des employés
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if (!$companyId && !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez sélectionner une entreprise.',
            ], 400);
        }

        $query = $companyId 
            ? Employee::forCompany($companyId)
            : Employee::query();

        // Filtre par statut actif
        if ($request->filled('active')) {
            $query->active();
        }

        // Recherche
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('first_name', 'like', '%' . $request->search . '%')
                  ->orWhere('last_name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', '%' . $request->search . '%')
                  ->orWhere('employee_number', 'like', '%' . $request->search . '%');
            });
        }

        // Tri
        $sortBy = $request->get('sort_by', 'last_name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $employees = $query->get();

        return response()->json([
            'success' => true,
            'data' => $employees,
        ], 200);
    }

    /**
     * Détails d'un employé
     */
    public function show($id)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if (!$companyId && !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez sélectionner une entreprise.',
            ], 400);
        }

        $employee = $companyId
            ? Employee::forCompany($companyId)->find($id)
            : Employee::find($id);

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employé non trouvé.',
            ], 404);
        }

        return response()->json($employee, 200);
    }

    /**
     * Créer un employé
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if (!$companyId && !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez sélectionner une entreprise.',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'employee_number' => 'nullable|string|max:255',
            'hire_date' => 'nullable|date',
            'hourly_rate' => 'nullable|numeric|min:0',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date',
            'id_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['company_id'] = $companyId;
        $data['is_active'] = $request->input('is_active', true);

        // Générer automatiquement le numéro d'employé si non fourni
        if (empty($data['employee_number'])) {
            $data['employee_number'] = $this->generateEmployeeNumber($companyId);
        }

        $employee = Employee::create($data);

        return response()->json($employee, 201);
    }

    /**
     * Mettre à jour un employé
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if (!$companyId && !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez sélectionner une entreprise.',
            ], 400);
        }

        $employee = $companyId
            ? Employee::forCompany($companyId)->find($id)
            : Employee::find($id);

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employé non trouvé.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'employee_number' => 'nullable|string|max:255',
            'hire_date' => 'nullable|date',
            'hourly_rate' => 'nullable|numeric|min:0',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date',
            'id_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $employee->update($validator->validated());

        return response()->json($employee, 200);
    }

    /**
     * Supprimer un employé
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if (!$companyId && !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez sélectionner une entreprise.',
            ], 400);
        }

        $employee = $companyId
            ? Employee::forCompany($companyId)->find($id)
            : Employee::find($id);

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employé non trouvé.',
            ], 404);
        }

        $employee->delete();

        return response()->json([
            'success' => true,
            'message' => 'Employé supprimé avec succès.',
        ], 200);
    }

    /**
     * Générer un numéro d'employé unique
     */
    private function generateEmployeeNumber($companyId)
    {
        $lastEmployee = Employee::forCompany($companyId)
            ->orderBy('id', 'desc')
            ->first();

        $number = $lastEmployee ? ((int) substr($lastEmployee->employee_number ?? '0', -4)) + 1 : 1;
        
        return 'EMP-' . str_pad($number, 4, '0', STR_PAD_LEFT);
    }
}

