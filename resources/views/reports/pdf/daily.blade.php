<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rapport Journalier - {{ $project->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            color: #333;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            background-color: #f0f0f0;
            padding: 10px;
            font-weight: bold;
            margin-bottom: 10px;
            border-left: 4px solid #007bff;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .stats {
            display: flex;
            justify-content: space-around;
            margin: 20px 0;
        }
        .stat-box {
            text-align: center;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
            min-width: 150px;
        }
        .stat-box h3 {
            margin: 0;
            font-size: 24px;
            color: #007bff;
        }
        .stat-box p {
            margin: 5px 0 0 0;
            color: #666;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #666;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Rapport Journalier</h1>
        <p><strong>Projet:</strong> {{ $project->name }}</p>
        <p><strong>Date:</strong> {{ $date->format('d/m/Y') }}</p>
        <p><strong>Entreprise:</strong> {{ $project->company->name ?? 'N/A' }}</p>
    </div>

    <div class="stats">
        <div class="stat-box">
            <h3>{{ $data['totalEmployees'] }}</h3>
            <p>Employés présents</p>
        </div>
        <div class="stat-box">
            <h3>{{ number_format($data['totalHours'], 1) }}h</h3>
            <p>Heures travaillées</p>
        </div>
        <div class="stat-box">
            <h3>{{ number_format($data['totalExpenses'], 2) }} FCFA</h3>
            <p>Dépenses du jour</p>
        </div>
        <div class="stat-box">
            <h3>{{ count($data['progressUpdates']) }}</h3>
            <p>Mises à jour</p>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Pointages du jour</div>
        @if($data['attendances']->count() > 0)
            <table>
                <thead>
                    <tr>
                        <th>Employé</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Heures</th>
                        <th>Heures sup.</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['attendances'] as $attendance)
                        <tr>
                            <td>{{ $attendance->employee->full_name ?? 'N/A' }}</td>
                            <td>{{ $attendance->check_in ?? '-' }}</td>
                            <td>{{ $attendance->check_out ?? '-' }}</td>
                            <td>{{ number_format($attendance->hours_worked ?? 0, 2) }}h</td>
                            <td>{{ number_format($attendance->overtime_hours ?? 0, 2) }}h</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p>Aucun pointage enregistré pour ce jour.</p>
        @endif
    </div>

    <div class="section">
        <div class="section-title">Mises à jour d'avancement</div>
        @if($data['progressUpdates']->count() > 0)
            <table>
                <thead>
                    <tr>
                        <th>Utilisateur</th>
                        <th>Avancement</th>
                        <th>Description</th>
                        <th>Heure</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['progressUpdates'] as $update)
                        <tr>
                            <td>{{ $update->user->name ?? 'N/A' }}</td>
                            <td>{{ $update->progress_percentage }}%</td>
                            <td>{{ Str::limit($update->description ?? '-', 50) }}</td>
                            <td>{{ $update->created_at->format('H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p>Aucune mise à jour enregistrée pour ce jour.</p>
        @endif
    </div>

    <div class="section">
        <div class="section-title">Dépenses du jour</div>
        @if($data['expenses']->count() > 0)
            <table>
                <thead>
                    <tr>
                        <th>Titre</th>
                        <th>Type</th>
                        <th>Montant</th>
                        <th>Fournisseur</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['expenses'] as $expense)
                        <tr>
                            <td>{{ $expense->title }}</td>
                            <td>{{ $expense->type_label }}</td>
                            <td>{{ number_format($expense->amount, 2) }} FCFA</td>
                            <td>{{ $expense->supplier ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="2">Total</th>
                        <th>{{ number_format($data['totalExpenses'], 2) }} FCFA</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        @else
            <p>Aucune dépense enregistrée pour ce jour.</p>
        @endif
    </div>

    <div class="section">
        <div class="section-title">Tâches du jour</div>
        @if($data['tasks']->count() > 0)
            <table>
                <thead>
                    <tr>
                        <th>Tâche</th>
                        <th>Assigné à</th>
                        <th>Statut</th>
                        <th>Priorité</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['tasks'] as $task)
                        <tr>
                            <td>{{ $task->title }}</td>
                            <td>{{ $task->assignedEmployee->full_name ?? 'Non assigné' }}</td>
                            <td>{{ $task->status_label }}</td>
                            <td>{{ $task->priority_label }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p>Aucune tâche liée à ce jour.</p>
        @endif
    </div>

    <div class="footer">
        <p>Rapport généré le {{ now()->format('d/m/Y à H:i') }}</p>
        <p>{{ $project->company->name ?? 'Chantix' }} - Tous droits réservés</p>
    </div>
</body>
</html>

