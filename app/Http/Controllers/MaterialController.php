<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Project;
use App\Models\ProjectMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class MaterialController extends Controller
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
                ->with('info', 'Veuillez sélectionner une entreprise pour accéder aux matériaux.');
        }

        // Vérifier les permissions : Admin ou Chef de Chantier peuvent gérer les matériaux
        if (!$user->hasPermission('materials.manage') && !$user->hasRoleInCompany('admin', $companyId)) {
            abort(403, 'Vous n\'avez pas la permission d\'accéder à la gestion des matériaux.');
        }

        $materials = Material::forCompany($companyId)
            ->with('company')
            ->orderBy('name')
            ->paginate(20);

        // Compter les matériaux avec stock faible
        $lowStockCount = Material::forCompany($companyId)
            ->active()
            ->get()
            ->filter(fn($material) => $material->isLowStock())
            ->count();

        return view('materials.index', compact('materials', 'lowStockCount'));
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

        return view('materials.create');
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
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'category' => 'nullable|string|max:255',
                'unit' => 'required|string|max:50',
                'unit_price' => 'nullable|numeric|min:0',
                'supplier' => 'nullable|string|max:255',
                'reference' => 'nullable|string|max:255',
                'stock_quantity' => 'nullable|numeric|min:0',
                'min_stock' => 'nullable|numeric|min:0',
            ]);

            $validated['company_id'] = $companyId;
            // Gérer is_active manuellement (checkbox non coché = false)
            $validated['is_active'] = $request->has('is_active') && $request->input('is_active') == '1';

            Material::create($validated);

            return redirect()->route('materials.index')
                ->with('success', 'Matériau créé avec succès.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la création du matériau: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Material $material)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if ($material->company_id !== $companyId) {
            abort(403, 'Accès non autorisé.');
        }

        // Charger les projets utilisant ce matériau
        $material->load('projects.company');

        return view('materials.show', compact('material'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Material $material)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if ($material->company_id !== $companyId) {
            abort(403, 'Accès non autorisé.');
        }

        return view('materials.edit', compact('material'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Material $material)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if ($material->company_id !== $companyId) {
            abort(403, 'Accès non autorisé.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:255',
            'unit' => 'required|string|max:50',
            'unit_price' => 'nullable|numeric|min:0',
            'supplier' => 'nullable|string|max:255',
            'reference' => 'nullable|string|max:255',
            'stock_quantity' => 'nullable|numeric|min:0',
            'min_stock' => 'nullable|numeric|min:0',
        ]);

        // Gérer is_active manuellement (checkbox non coché = false)
        $validated['is_active'] = $request->has('is_active') && $request->input('is_active') == '1';

        $material->update($validated);

        return redirect()->route('materials.index')
            ->with('success', 'Matériau mis à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Material $material)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if ($material->company_id !== $companyId) {
            abort(403, 'Accès non autorisé.');
        }

        // Vérifier si le matériau est utilisé dans des projets
        if ($material->projects()->count() > 0) {
            return redirect()->route('materials.index')
                ->with('error', 'Impossible de supprimer ce matériau car il est utilisé dans des projets.');
        }

        $material->delete();

        return redirect()->route('materials.index')
            ->with('success', 'Matériau supprimé avec succès.');
    }

    /**
     * Ajouter un matériau à un projet
     */
    public function addToProject(Request $request, Project $project)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if ($project->company_id !== $companyId) {
            abort(403, 'Accès non autorisé.');
        }

        $validated = $request->validate([
            'material_id' => 'required|exists:materials,id',
            'quantity_planned' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        // Vérifier que le matériau appartient à la même entreprise
        $material = Material::findOrFail($validated['material_id']);
        if ($material->company_id !== $companyId) {
            return redirect()->back()
                ->with('error', 'Le matériau sélectionné n\'appartient pas à votre entreprise.');
        }

        // Vérifier si le matériau est déjà associé au projet
        if ($project->materials()->where('materials.id', $validated['material_id'])->exists()) {
            return redirect()->back()
                ->with('error', 'Ce matériau est déjà associé à ce projet.');
        }

        $project->materials()->attach($validated['material_id'], [
            'quantity_planned' => $validated['quantity_planned'],
            'quantity_remaining' => $validated['quantity_planned'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->back()
            ->with('success', 'Matériau ajouté au projet avec succès.');
    }

    /**
     * Mettre à jour les quantités d'un matériau dans un projet
     */
    public function updateProjectMaterial(Request $request, Project $project, Material $material)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if ($project->company_id !== $companyId || $material->company_id !== $companyId) {
            abort(403, 'Accès non autorisé.');
        }

        $validated = $request->validate([
            'quantity_ordered' => 'nullable|numeric|min:0',
            'quantity_delivered' => 'nullable|numeric|min:0',
            'quantity_used' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $projectMaterial = $project->projectMaterials()
            ->where('material_id', $material->id)
            ->firstOrFail();

        if (isset($validated['quantity_ordered'])) {
            $projectMaterial->quantity_ordered = $validated['quantity_ordered'];
        }
        if (isset($validated['quantity_delivered'])) {
            $projectMaterial->quantity_delivered = $validated['quantity_delivered'];
        }
        if (isset($validated['quantity_used'])) {
            $projectMaterial->quantity_used = $validated['quantity_used'];
        }
        if (isset($validated['notes'])) {
            $projectMaterial->notes = $validated['notes'];
        }

        $projectMaterial->calculateRemaining();
        $projectMaterial->save();

        return redirect()->back()
            ->with('success', 'Quantités mises à jour avec succès.');
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

        return view('materials.import');
    }

    /**
     * Télécharger le template Excel
     */
    public function downloadTemplate()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // En-têtes
        $headers = ['Nom', 'Description', 'Catégorie', 'Unité', 'Prix unitaire', 'Stock actuel', 'Stock minimum', 'Référence', 'Fournisseur'];
        $sheet->fromArray($headers, null, 'A1');
        
        // Exemple de données
        $example = ['Ciment Portland', 'Ciment pour béton', 'Ciment', 'kg', '0.50', '1000', '100', 'CEM-001', 'Fournisseur A'];
        $sheet->fromArray($example, null, 'A2');
        
        // Style des en-têtes
        $sheet->getStyle('A1:I1')->getFont()->setBold(true);
        $sheet->getStyle('A1:I1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE0E0E0');
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'template_materiaux.xlsx';
        $path = storage_path('app/temp/' . $filename);
        $writer->save($path);
        
        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    /**
     * Importer des matériaux depuis Excel
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
                
                if (empty($row[0])) continue; // Ignorer les lignes vides
                
                try {
                    Material::create([
                        'company_id' => $companyId,
                        'name' => $row[0] ?? '',
                        'description' => $row[1] ?? null,
                        'category' => $row[2] ?? null,
                        'unit' => $row[3] ?? 'unité',
                        'price' => isset($row[4]) ? (float)$row[4] : 0,
                        'current_stock' => isset($row[5]) ? (float)$row[5] : 0,
                        'min_stock_level' => isset($row[6]) ? (float)$row[6] : 0,
                        'is_active' => true,
                    ]);
                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = 'Ligne ' . ($i + 1) . ': ' . $e->getMessage();
                }
            }

            $message = $imported . ' matériau(x) importé(s) avec succès.';
            if (count($errors) > 0) {
                $message .= ' ' . count($errors) . ' erreur(s).';
            }

            return redirect()->route('materials.index')
                ->with('success', $message)
                ->with('import_errors', $errors);
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erreur lors de l\'import: ' . $e->getMessage());
        }
    }

    /**
     * Afficher le formulaire de transfert de matériaux
     */
    public function showTransfer(Project $project, Material $material)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if ($project->company_id !== $companyId || $material->company_id !== $companyId) {
            abort(403, 'Accès non autorisé.');
        }

        // Vérifier que le matériau est dans le projet
        $projectMaterial = $project->projectMaterials()
            ->where('material_id', $material->id)
            ->firstOrFail();

        // Récupérer les autres projets de l'entreprise
        $otherProjects = Project::forCompany($companyId)
            ->where('id', '!=', $project->id)
            ->orderBy('name')
            ->get();

        return view('materials.transfer', compact('project', 'material', 'projectMaterial', 'otherProjects'));
    }

    /**
     * Transférer des matériaux entre projets
     */
    public function transfer(Request $request, Project $sourceProject, Material $material)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if ($sourceProject->company_id !== $companyId || $material->company_id !== $companyId) {
            abort(403, 'Accès non autorisé.');
        }

        $validated = $request->validate([
            'destination_project_id' => 'required|exists:projects,id',
            'quantity' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:500',
        ]);

        $destinationProject = Project::findOrFail($validated['destination_project_id']);

        // Vérifier que le projet destination appartient à la même entreprise
        if ($destinationProject->company_id !== $companyId) {
            return redirect()->back()
                ->with('error', 'Le projet destination doit appartenir à la même entreprise.');
        }

        // Vérifier que le matériau est dans le projet source
        $sourceProjectMaterial = $sourceProject->projectMaterials()
            ->where('material_id', $material->id)
            ->firstOrFail();

        // Vérifier que la quantité disponible est suffisante
        $availableQuantity = $sourceProjectMaterial->quantity_remaining;
        if ($validated['quantity'] > $availableQuantity) {
            return redirect()->back()
                ->with('error', "Quantité insuffisante. Quantité disponible: {$availableQuantity} {$material->unit}");
        }

        DB::beginTransaction();
        try {
            // Déduire du projet source
            $sourceProjectMaterial->quantity_used += $validated['quantity'];
            $sourceProjectMaterial->quantity_remaining = max(0, $sourceProjectMaterial->quantity_remaining - $validated['quantity']);
            $sourceProjectMaterial->save();

            // Ajouter au projet destination
            $destinationProjectMaterial = $destinationProject->projectMaterials()
                ->where('material_id', $material->id)
                ->first();

            if ($destinationProjectMaterial) {
                // Le matériau existe déjà dans le projet destination
                $destinationProjectMaterial->quantity_delivered += $validated['quantity'];
                $destinationProjectMaterial->quantity_remaining += $validated['quantity'];
                if ($destinationProjectMaterial->notes) {
                    $destinationProjectMaterial->notes .= "\n" . ($validated['notes'] ?? '');
                } else {
                    $destinationProjectMaterial->notes = $validated['notes'] ?? null;
                }
                $destinationProjectMaterial->save();
            } else {
                // Créer une nouvelle entrée pour le projet destination
                ProjectMaterial::create([
                    'project_id' => $destinationProject->id,
                    'material_id' => $material->id,
                    'quantity_planned' => 0,
                    'quantity_ordered' => 0,
                    'quantity_delivered' => $validated['quantity'],
                    'quantity_used' => 0,
                    'quantity_remaining' => $validated['quantity'],
                    'notes' => 'Transfert depuis le projet "' . $sourceProject->name . '". ' . ($validated['notes'] ?? ''),
                ]);
            }

            DB::commit();

            return redirect()->route('projects.show', $sourceProject)
                ->with('success', "Transfert de {$validated['quantity']} {$material->unit} de {$material->name} vers le projet '{$destinationProject->name}' effectué avec succès.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Erreur lors du transfert: ' . $e->getMessage());
        }
    }
}
