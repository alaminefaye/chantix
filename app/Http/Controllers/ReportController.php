<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Report;
use App\Models\Attendance;
use App\Models\Expense;
use App\Models\ProgressUpdate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ReportController extends Controller
{
    /**
     * Afficher la page de génération de rapports
     */
    public function index(Project $project)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if ($project->company_id !== $companyId) {
            abort(403, 'Accès non autorisé.');
        }

        $reports = $project->reports()
            ->with('creator')
            ->orderBy('report_date', 'desc')
            ->paginate(20);

        return view('reports.index', compact('project', 'reports'));
    }

    /**
     * Générer un rapport journalier
     */
    public function generateDaily(Project $project, Request $request)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if ($project->company_id !== $companyId) {
            abort(403, 'Accès non autorisé.');
        }

        $date = $request->get('date', now()->format('Y-m-d'));
        $dateObj = Carbon::parse($date);

        // Collecter les données
        $data = $this->collectDailyData($project, $dateObj);

        // Générer le PDF
        $pdf = Pdf::loadView('reports.pdf.daily', [
            'project' => $project,
            'date' => $dateObj,
            'data' => $data,
        ]);

        // Sauvegarder le rapport
        $report = Report::create([
            'project_id' => $project->id,
            'created_by' => $user->id,
            'type' => 'journalier',
            'report_date' => $dateObj,
            'data' => $data,
        ]);

        return $pdf->download('rapport-journalier-' . $project->name . '-' . $dateObj->format('Y-m-d') . '.pdf');
    }

    /**
     * Générer un rapport hebdomadaire
     */
    public function generateWeekly(Project $project, Request $request)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if ($project->company_id !== $companyId) {
            abort(403, 'Accès non autorisé.');
        }

        $startDate = $request->get('start_date', now()->startOfWeek()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->endOfWeek()->format('Y-m-d'));
        
        $startDateObj = Carbon::parse($startDate);
        $endDateObj = Carbon::parse($endDate);

        // Collecter les données
        $data = $this->collectWeeklyData($project, $startDateObj, $endDateObj);

        // Générer le PDF
        $pdf = Pdf::loadView('reports.pdf.weekly', [
            'project' => $project,
            'startDate' => $startDateObj,
            'endDate' => $endDateObj,
            'data' => $data,
        ]);

        // Sauvegarder le rapport
        $report = Report::create([
            'project_id' => $project->id,
            'created_by' => $user->id,
            'type' => 'hebdomadaire',
            'report_date' => $startDateObj,
            'end_date' => $endDateObj,
            'data' => $data,
        ]);

        return $pdf->download('rapport-hebdomadaire-' . $project->name . '-' . $startDateObj->format('Y-m-d') . '.pdf');
    }

    /**
     * Collecter les données pour un rapport journalier
     */
    private function collectDailyData(Project $project, Carbon $date)
    {
        // Pointages du jour
        $attendances = $project->attendances()
            ->with('employee')
            ->whereDate('date', $date)
            ->get();

        $totalEmployees = $attendances->where('is_present', true)->count();
        $totalHours = $attendances->sum('hours_worked');
        $totalOvertime = $attendances->sum('overtime_hours');

        // Mises à jour du jour
        $progressUpdates = $project->progressUpdates()
            ->whereDate('created_at', $date)
            ->with('user')
            ->get();

        // Photos du jour
        $photos = [];
        foreach ($progressUpdates as $update) {
            if ($update->photos) {
                $photos = array_merge($photos, $update->photos);
            }
        }

        // Dépenses du jour
        $expenses = $project->expenses()
            ->whereDate('expense_date', $date)
            ->get();

        $totalExpenses = $expenses->sum('amount');

        // Tâches du jour
        $tasks = $project->tasks()
            ->where(function($query) use ($date) {
                $query->whereDate('start_date', $date)
                    ->orWhereDate('deadline', $date)
                    ->orWhereDate('updated_at', $date);
            })
            ->with('assignedEmployee')
            ->get();

        return [
            'attendances' => $attendances,
            'totalEmployees' => $totalEmployees,
            'totalHours' => $totalHours,
            'totalOvertime' => $totalOvertime,
            'progressUpdates' => $progressUpdates,
            'photos' => $photos,
            'expenses' => $expenses,
            'totalExpenses' => $totalExpenses,
            'tasks' => $tasks,
        ];
    }

    /**
     * Collecter les données pour un rapport hebdomadaire
     */
    private function collectWeeklyData(Project $project, Carbon $startDate, Carbon $endDate)
    {
        // Pointages de la semaine
        $attendances = $project->attendances()
            ->with('employee')
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $totalEmployees = $attendances->where('is_present', true)->count();
        $totalHours = $attendances->sum('hours_worked');
        $totalOvertime = $attendances->sum('overtime_hours');

        // Mises à jour de la semaine
        $progressUpdates = $project->progressUpdates()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('user')
            ->get();

        // Dépenses de la semaine
        $expenses = $project->expenses()
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->get();

        $totalExpenses = $expenses->sum('amount');
        $expensesByType = $expenses->groupBy('type')->map->sum('amount');

        // Tâches de la semaine
        $tasks = $project->tasks()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orWhereBetween('updated_at', [$startDate, $endDate])
            ->with('assignedEmployee')
            ->get();

        $tasksByStatus = $tasks->groupBy('status')->map->count();
        $overdueTasks = $project->tasks()->overdue()->count();

        // Évolution de l'avancement
        $progressEvolution = [];
        $currentDate = $startDate->copy();
        $lastProgress = $project->progress;
        
        while ($currentDate <= $endDate) {
            $update = $project->progressUpdates()
                ->whereDate('created_at', '<=', $currentDate)
                ->orderBy('created_at', 'desc')
                ->first();
            
            if ($update) {
                $lastProgress = $update->progress_percentage;
            }
            
            $progressEvolution[] = [
                'date' => $currentDate->format('d/m'),
                'progress' => $lastProgress,
            ];
            
            $currentDate->addDay();
        }

        return [
            'attendances' => $attendances,
            'totalEmployees' => $totalEmployees,
            'totalHours' => $totalHours,
            'totalOvertime' => $totalOvertime,
            'progressUpdates' => $progressUpdates,
            'expenses' => $expenses,
            'totalExpenses' => $totalExpenses,
            'expensesByType' => $expensesByType,
            'tasks' => $tasks,
            'tasksByStatus' => $tasksByStatus,
            'overdueTasks' => $overdueTasks,
            'progressEvolution' => $progressEvolution,
        ];
    }

    /**
     * Exporter le rapport journalier en Excel
     */
    public function exportDailyExcel(Project $project, Request $request)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if ($project->company_id !== $companyId) {
            abort(403, 'Accès non autorisé.');
        }

        $date = $request->get('date', now()->format('Y-m-d'));
        $dateObj = Carbon::parse($date);

        // Récupérer les données
        $data = $this->getDailyReportData($project, $dateObj);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rapport Journalier');

        // En-tête
        $sheet->setCellValue('A1', 'Rapport Journalier - ' . $project->name);
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'Date: ' . $dateObj->format('d/m/Y'));
        $sheet->mergeCells('A2:D2');

        $row = 4;

        // Présences
        $sheet->setCellValue('A' . $row, 'PRÉSENCES');
        $sheet->mergeCells('A' . $row . ':D' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E3F2FD');
        $row++;

        $sheet->setCellValue('A' . $row, 'Employé');
        $sheet->setCellValue('B' . $row, 'Check-in');
        $sheet->setCellValue('C' . $row, 'Check-out');
        $sheet->setCellValue('D' . $row, 'Heures');
        $sheet->getStyle('A' . $row . ':D' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row . ':D' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('BBDEFB');
        $row++;

        foreach ($data['attendances'] as $attendance) {
            $sheet->setCellValue('A' . $row, $attendance->employee->full_name);
            $sheet->setCellValue('B' . $row, $attendance->check_in ? $attendance->check_in : '-');
            $sheet->setCellValue('C' . $row, $attendance->check_out ? $attendance->check_out : '-');
            $sheet->setCellValue('D' . $row, $attendance->hours_worked ? number_format($attendance->hours_worked, 2) . 'h' : '-');
            $row++;
        }

        $row += 2;

        // Dépenses
        $sheet->setCellValue('A' . $row, 'DÉPENSES');
        $sheet->mergeCells('A' . $row . ':D' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8F5E9');
        $row++;

        $sheet->setCellValue('A' . $row, 'Description');
        $sheet->setCellValue('B' . $row, 'Type');
        $sheet->setCellValue('C' . $row, 'Montant');
        $sheet->setCellValue('D' . $row, 'Date');
        $sheet->getStyle('A' . $row . ':D' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row . ':D' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('C8E6C9');
        $row++;

        foreach ($data['expenses'] as $expense) {
            $sheet->setCellValue('A' . $row, $expense->title);
            $sheet->setCellValue('B' . $row, ucfirst($expense->type));
            $sheet->setCellValue('C' . $row, number_format($expense->amount, 2, ',', ' ') . ' €');
            $sheet->setCellValue('D' . $row, $expense->expense_date->format('d/m/Y'));
            $row++;
        }

        $row++;
        $sheet->setCellValue('B' . $row, 'Total:');
        $sheet->setCellValue('C' . $row, number_format($data['totalExpenses'], 2, ',', ' ') . ' €');
        $sheet->getStyle('B' . $row . ':C' . $row)->getFont()->setBold(true);

        // Ajuster les largeurs
        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(15);

        // Générer le fichier
        $writer = new Xlsx($spreadsheet);
        $filename = 'rapport-journalier-' . $project->name . '-' . $dateObj->format('Y-m-d') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    /**
     * Exporter le rapport hebdomadaire en Excel
     */
    public function exportWeeklyExcel(Project $project, Request $request)
    {
        $user = Auth::user();
        $companyId = $user->current_company_id;

        if ($project->company_id !== $companyId) {
            abort(403, 'Accès non autorisé.');
        }

        $startDate = $request->get('start_date', now()->startOfWeek()->format('Y-m-d'));
        $startDateObj = Carbon::parse($startDate);
        $endDateObj = $startDateObj->copy()->endOfWeek();

        // Récupérer les données
        $data = $this->getWeeklyReportData($project, $startDateObj, $endDateObj);

        $spreadsheet = new Spreadsheet();
        
        // Feuille 1: Résumé
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Résumé');

        $sheet->setCellValue('A1', 'Rapport Hebdomadaire - ' . $project->name);
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'Période: ' . $startDateObj->format('d/m/Y') . ' - ' . $endDateObj->format('d/m/Y'));
        $sheet->mergeCells('A2:D2');

        $row = 4;
        $sheet->setCellValue('A' . $row, 'Indicateur');
        $sheet->setCellValue('B' . $row, 'Valeur');
        $sheet->getStyle('A' . $row . ':B' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row . ':B' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E3F2FD');
        $row++;

        $sheet->setCellValue('A' . $row, 'Total employés présents');
        $sheet->setCellValue('B' . $row, $data['totalEmployees']);
        $row++;

        $sheet->setCellValue('A' . $row, 'Total heures travaillées');
        $sheet->setCellValue('B' . $row, number_format($data['totalHours'], 2) . 'h');
        $row++;

        $sheet->setCellValue('A' . $row, 'Total heures supplémentaires');
        $sheet->setCellValue('B' . $row, number_format($data['totalOvertime'], 2) . 'h');
        $row++;

        $sheet->setCellValue('A' . $row, 'Total dépenses');
        $sheet->setCellValue('B' . $row, number_format($data['totalExpenses'], 2, ',', ' ') . ' €');
        $row++;

        $sheet->setCellValue('A' . $row, 'Tâches en retard');
        $sheet->setCellValue('B' . $row, $data['overdueTasks']);

        // Feuille 2: Dépenses
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Dépenses');
        $row = 1;

        $sheet2->setCellValue('A' . $row, 'Description');
        $sheet2->setCellValue('B' . $row, 'Type');
        $sheet2->setCellValue('C' . $row, 'Montant');
        $sheet2->setCellValue('D' . $row, 'Date');
        $sheet2->getStyle('A' . $row . ':D' . $row)->getFont()->setBold(true);
        $sheet2->getStyle('A' . $row . ':D' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('C8E6C9');
        $row++;

        foreach ($data['expenses'] as $expense) {
            $sheet2->setCellValue('A' . $row, $expense->title);
            $sheet2->setCellValue('B' . $row, ucfirst($expense->type));
            $sheet2->setCellValue('C' . $row, number_format($expense->amount, 2, ',', ' ') . ' €');
            $sheet2->setCellValue('D' . $row, $expense->expense_date->format('d/m/Y'));
            $row++;
        }

        // Ajuster les largeurs
        foreach (['A', 'B', 'C', 'D'] as $col) {
            $sheet->getColumnDimension($col)->setWidth(25);
            $sheet2->getColumnDimension($col)->setWidth(25);
        }

        // Générer le fichier
        $writer = new Xlsx($spreadsheet);
        $filename = 'rapport-hebdomadaire-' . $project->name . '-' . $startDateObj->format('Y-m-d') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }
}
