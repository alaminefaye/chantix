<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Barryvdh\DomPDF\Facade\Pdf;

class AttendanceController extends Controller
{
    /**
     * Afficher la liste des pointages pour un projet
     */
    public function index(Project $project)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if ($project->company_id !== $companyId) {
            abort(403, 'Accès non autorisé.');
        }

        $date = request()->get('date', now()->format('Y-m-d'));
        $employeeId = request()->get('employee_id');

        $query = $project->attendances()
            ->with('employee')
            ->whereDate('date', $date)
            ->orderBy('check_in', 'asc');

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        $attendances = $query->get();
        $employees = $project->employees()->wherePivot('is_active', true)->get();

        return view('attendances.index', compact('project', 'attendances', 'employees', 'date', 'employeeId'));
    }

    /**
     * Afficher le formulaire de pointage
     */
    public function create(Project $project)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if ($project->company_id !== $companyId) {
            abort(403, 'Accès non autorisé.');
        }

        $employees = $project->employees()->wherePivot('is_active', true)->get();
        $date = request()->get('date', now()->format('Y-m-d'));

        return view('attendances.create', compact('project', 'employees', 'date'));
    }

    /**
     * Enregistrer un pointage (check-in)
     */
    public function checkIn(Request $request, Project $project)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if ($project->company_id !== $companyId) {
            abort(403, 'Accès non autorisé.');
        }

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'check_in' => 'required|date_format:H:i',
            'check_in_location' => 'nullable|string|max:255',
            'check_in_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'notes' => 'nullable|string',
        ]);

        // Vérifier que l'employé est affecté au projet
        $employee = Employee::findOrFail($validated['employee_id']);
        if (!$employee->isAssignedToProject($project->id)) {
            return redirect()->back()
                ->with('error', 'Cet employé n\'est pas affecté à ce projet.');
        }

        // Vérifier si un pointage existe déjà pour cette date
        $existing = Attendance::where('project_id', $project->id)
            ->where('employee_id', $validated['employee_id'])
            ->whereDate('date', $validated['date'])
            ->first();

        if ($existing) {
            return redirect()->back()
                ->with('error', 'Un pointage existe déjà pour cet employé à cette date.');
        }

        // Upload de la photo de check-in si fournie
        $checkInPhoto = null;
        if ($request->hasFile('check_in_photo')) {
            $checkInPhoto = $request->file('check_in_photo')->store('attendances/check-in', 'public');
        }

        $attendance = Attendance::create([
            'project_id' => $project->id,
            'employee_id' => $validated['employee_id'],
            'date' => $validated['date'],
            'check_in' => $validated['check_in'],
            'check_in_location' => $validated['check_in_location'] ?? null,
            'check_in_photo' => $checkInPhoto,
            'notes' => $validated['notes'] ?? null,
            'is_present' => true,
        ]);

        return redirect()->route('attendances.index', $project)
            ->with('success', 'Check-in enregistré avec succès.');
    }

    /**
     * Enregistrer un check-out
     */
    public function checkOut(Request $request, Attendance $attendance)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if ($attendance->project->company_id !== $companyId) {
            abort(403, 'Accès non autorisé.');
        }

        $validated = $request->validate([
            'check_out' => 'required|date_format:H:i',
            'check_out_location' => 'nullable|string|max:255',
            'check_out_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'notes' => 'nullable|string',
        ]);

        if ($attendance->check_out) {
            return redirect()->back()
                ->with('error', 'Le check-out a déjà été enregistré pour ce pointage.');
        }

        // Upload de la photo de check-out si fournie
        $checkOutPhoto = null;
        if ($request->hasFile('check_out_photo')) {
            $checkOutPhoto = $request->file('check_out_photo')->store('attendances/check-out', 'public');
        }

        $attendance->check_out = $validated['check_out'];
        $attendance->check_out_location = $validated['check_out_location'] ?? null;
        $attendance->check_out_photo = $checkOutPhoto;
        
        if ($attendance->notes) {
            $attendance->notes .= "\n" . ($validated['notes'] ?? '');
        } else {
            $attendance->notes = $validated['notes'] ?? null;
        }

        $attendance->calculateHoursWorked();
        $attendance->save();

        return redirect()->back()
            ->with('success', 'Check-out enregistré avec succès.');
    }

    /**
     * Enregistrer une absence
     */
    public function markAbsence(Request $request, Project $project)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if ($project->company_id !== $companyId) {
            abort(403, 'Accès non autorisé.');
        }

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'absence_reason' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        // Vérifier que l'employé est affecté au projet
        $employee = Employee::findOrFail($validated['employee_id']);
        if (!$employee->isAssignedToProject($project->id)) {
            return redirect()->back()
                ->with('error', 'Cet employé n\'est pas affecté à ce projet.');
        }

        // Vérifier si un pointage existe déjà pour cette date
        $existing = Attendance::where('project_id', $project->id)
            ->where('employee_id', $validated['employee_id'])
            ->whereDate('date', $validated['date'])
            ->first();

        if ($existing) {
            return redirect()->back()
                ->with('error', 'Un pointage existe déjà pour cet employé à cette date.');
        }

        Attendance::create([
            'project_id' => $project->id,
            'employee_id' => $validated['employee_id'],
            'date' => $validated['date'],
            'is_present' => false,
            'absence_reason' => $validated['absence_reason'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('attendances.index', $project)
            ->with('success', 'Absence enregistrée avec succès.');
    }

    /**
     * Mettre à jour un pointage
     */
    public function update(Request $request, Attendance $attendance)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if ($attendance->project->company_id !== $companyId) {
            abort(403, 'Accès non autorisé.');
        }

        $validated = $request->validate([
            'check_in' => 'nullable|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i',
            'check_in_location' => 'nullable|string|max:255',
            'check_out_location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'absence_reason' => 'nullable|string|max:255',
        ]);

        if (isset($validated['check_in'])) {
            $attendance->check_in = $validated['check_in'];
        }
        if (isset($validated['check_out'])) {
            $attendance->check_out = $validated['check_out'];
        }
        if (isset($validated['check_in_location'])) {
            $attendance->check_in_location = $validated['check_in_location'];
        }
        if (isset($validated['check_out_location'])) {
            $attendance->check_out_location = $validated['check_out_location'];
        }
        if (isset($validated['notes'])) {
            $attendance->notes = $validated['notes'];
        }
        if (isset($validated['absence_reason'])) {
            $attendance->absence_reason = $validated['absence_reason'];
        }

        if ($attendance->check_in && $attendance->check_out) {
            $attendance->calculateHoursWorked();
        }

        $attendance->save();

        return redirect()->back()
            ->with('success', 'Pointage mis à jour avec succès.');
    }

    /**
     * Supprimer un pointage
     */
    public function destroy(Attendance $attendance)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if ($attendance->project->company_id !== $companyId) {
            abort(403, 'Accès non autorisé.');
        }

        $attendance->delete();

        return redirect()->back()
            ->with('success', 'Pointage supprimé avec succès.');
    }
}
