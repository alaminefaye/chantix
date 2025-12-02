<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\IOFactory;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if (!$companyId) {
            return redirect()->route('companies.index')
                ->with('info', 'Veuillez sélectionner une entreprise pour accéder aux employés.');
        }

        // Vérifier les permissions : Admin ou Chef de Chantier peuvent gérer les employés
        if (!$user->hasPermission('projects.manage_team') && !$user->hasRoleInCompany('admin', $companyId)) {
            abort(403, 'Vous n\'avez pas la permission d\'accéder à la gestion des employés.');
        }

        $employees = Employee::forCompany($companyId)
            ->with('company')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(20);

        return view('employees.index', compact('employees'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if (!$companyId) {
            return redirect()->route('companies.index')
                ->with('info', 'Veuillez sélectionner une entreprise.');
        }

        return view('employees.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if (!$companyId) {
            return redirect()->route('companies.index')
                ->with('error', 'Veuillez sélectionner une entreprise.');
        }

        try {
            $validated = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:255',
                'position' => 'nullable|string|max:255',
                'employee_number' => 'nullable|string|max:255|unique:employees,employee_number',
                'hire_date' => 'nullable|date',
                'hourly_rate' => 'nullable|numeric|min:0',
                'address' => 'nullable|string',
                'city' => 'nullable|string|max:255',
                'country' => 'nullable|string|max:255',
                'birth_date' => 'nullable|date',
                'id_number' => 'nullable|string|max:255',
                'notes' => 'nullable|string',
            ]);

            $validated['company_id'] = $companyId;
            // Gérer is_active manuellement (checkbox non coché = false)
            $validated['is_active'] = $request->has('is_active') && $request->input('is_active') == '1';

            Employee::create($validated);

            return redirect()->route('employees.index')
                ->with('success', 'Employé créé avec succès.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la création de l\'employé: ' . $e->getMessage())
                ->withInput();
        }

        Employee::create($validated);

        return redirect()->route('employees.index')
            ->with('success', 'Employé créé avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Employee $employee)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if ($employee->company_id !== $companyId) {
            abort(403, 'Accès non autorisé.');
        }

        $employee->load('company', 'projects', 'attendances.project');

        // Statistiques
        $totalProjects = $employee->projects()->count();
        $activeProjects = $employee->projects()->wherePivot('is_active', true)->count();
        $totalHours = $employee->attendances()->sum('hours_worked');
        $totalOvertime = $employee->attendances()->sum('overtime_hours');

        return view('employees.show', compact('employee', 'totalProjects', 'activeProjects', 'totalHours', 'totalOvertime'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Employee $employee)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if ($employee->company_id !== $companyId) {
            abort(403, 'Accès non autorisé.');
        }

        return view('employees.edit', compact('employee'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Employee $employee)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if ($employee->company_id !== $companyId) {
            abort(403, 'Accès non autorisé.');
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'employee_number' => 'nullable|string|max:255|unique:employees,employee_number,' . $employee->id,
            'hire_date' => 'nullable|date',
            'hourly_rate' => 'nullable|numeric|min:0',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date',
            'id_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        // Gérer is_active manuellement (checkbox non coché = false)
        $validated['is_active'] = $request->has('is_active') && $request->input('is_active') == '1';

        $employee->update($validated);

        return redirect()->route('employees.index')
            ->with('success', 'Employé mis à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Employee $employee)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if ($employee->company_id !== $companyId) {
            abort(403, 'Accès non autorisé.');
        }

        // Vérifier si l'employé est affecté à des projets actifs
        if ($employee->projects()->wherePivot('is_active', true)->count() > 0) {
            return redirect()->route('employees.index')
                ->with('error', 'Impossible de supprimer cet employé car il est affecté à des projets actifs.');
        }

        $employee->delete();

        return redirect()->route('employees.index')
            ->with('success', 'Employé supprimé avec succès.');
    }

    /**
     * Assigner un employé à un projet
     */
    public function assignToProject(Request $request, Project $project)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if ($project->company_id !== $companyId) {
            abort(403, 'Accès non autorisé.');
        }

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'assigned_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        // Vérifier que l'employé appartient à la même entreprise
        $employee = Employee::findOrFail($validated['employee_id']);
        if ($employee->company_id !== $companyId) {
            return redirect()->back()
                ->with('error', 'L\'employé sélectionné n\'appartient pas à votre entreprise.');
        }

        // Vérifier si l'employé est déjà affecté au projet et actif
        $existingAssignment = $project->employees()->where('employees.id', $validated['employee_id'])->first();
        if ($existingAssignment && $existingAssignment->pivot->is_active) {
            return redirect()->back()
                ->with('error', 'Cet employé est déjà affecté à ce projet.');
        }
        
        // Si l'employé était déjà affecté mais inactif, le réactiver
        if ($existingAssignment && !$existingAssignment->pivot->is_active) {
            $project->employees()->updateExistingPivot($validated['employee_id'], [
                'assigned_date' => $validated['assigned_date'] ?? now(),
                'notes' => $validated['notes'] ?? $existingAssignment->pivot->notes,
                'is_active' => true,
                'end_date' => null,
            ]);
            
            return redirect()->back()
                ->with('success', 'Employé réactivé sur le projet avec succès.');
        }

        $project->employees()->attach($validated['employee_id'], [
            'assigned_date' => $validated['assigned_date'] ?? now(),
            'notes' => $validated['notes'] ?? null,
            'is_active' => true,
        ]);

        return redirect()->back()
            ->with('success', 'Employé affecté au projet avec succès.');
    }

    /**
     * Retirer un employé d'un projet
     */
    public function removeFromProject(Project $project, Employee $employee)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if ($project->company_id !== $companyId || $employee->company_id !== $companyId) {
            abort(403, 'Accès non autorisé.');
        }

        // Vérifier si l'employé est affecté au projet
        if (!$project->employees()->where('employees.id', $employee->id)->exists()) {
            return redirect()->back()
                ->with('error', 'Cet employé n\'est pas affecté à ce projet.');
        }

        // Désactiver l'employé (marquer comme inactif au lieu de supprimer pour garder l'historique)
        $project->employees()->updateExistingPivot($employee->id, [
            'is_active' => false,
            'end_date' => now(),
        ]);

        return redirect()->back()
            ->with('success', 'Employé retiré du projet avec succès.');
    }

    /**
     * Afficher le formulaire d'import Excel
     */
    public function showImport()
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if (!$companyId) {
            return redirect()->route('companies.index')
                ->with('info', 'Veuillez sélectionner une entreprise.');
        }

        return view('employees.import');
    }

    /**
     * Télécharger le template Excel
     */
    public function downloadTemplate()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // En-têtes
        $headers = ['Prénom', 'Nom', 'Email', 'Téléphone', 'Poste', 'Taux horaire', 'Adresse', 'Ville', 'Pays'];
        $sheet->fromArray($headers, null, 'A1');
        
        // Exemple de données
        $example = ['Jean', 'Dupont', 'jean.dupont@example.com', '0123456789', 'Ouvrier', '15.00', '123 Rue Example', 'Paris', 'France'];
        $sheet->fromArray($example, null, 'A2');
        
        // Style des en-têtes
        $sheet->getStyle('A1:I1')->getFont()->setBold(true);
        $sheet->getStyle('A1:I1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE0E0E0');
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'template_employes.xlsx';
        $path = storage_path('app/temp/' . $filename);
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        $writer->save($path);
        
        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    /**
     * Importer des employés depuis Excel
     */
    public function import(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if (!$companyId) {
            return redirect()->route('companies.index')
                ->with('error', 'Veuillez sélectionner une entreprise.');
        }

        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            $spreadsheet = IOFactory::load($request->file('file'));
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();
            
            $imported = 0;
            $errors = [];
            
            // Ignorer la première ligne (en-têtes)
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                
                if (empty($row[0]) || empty($row[1])) continue; // Ignorer les lignes vides
                
                try {
                    Employee::create([
                        'company_id' => $companyId,
                        'first_name' => $row[0] ?? '',
                        'last_name' => $row[1] ?? '',
                        'email' => $row[2] ?? null,
                        'phone' => $row[3] ?? null,
                        'position' => $row[4] ?? null,
                        'hourly_rate' => isset($row[5]) ? (float)$row[5] : null,
                        'address' => $row[6] ?? null,
                        'city' => $row[7] ?? null,
                        'country' => $row[8] ?? null,
                        'is_active' => true,
                    ]);
                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = 'Ligne ' . ($i + 1) . ': ' . $e->getMessage();
                }
            }

            $message = $imported . ' employé(s) importé(s) avec succès.';
            if (count($errors) > 0) {
                $message .= ' ' . count($errors) . ' erreur(s).';
            }

            return redirect()->route('employees.index')
                ->with('success', $message)
                ->with('import_errors', $errors);
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erreur lors de l\'import: ' . $e->getMessage());
        }
    }
}
