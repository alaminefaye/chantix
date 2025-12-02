<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rapport Hebdomadaire - {{ $project->name }}</title>
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
            border-left: 4px solid #28a745;
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
            flex-wrap: wrap;
        }
        .stat-box {
            text-align: center;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
            min-width: 150px;
            margin: 5px;
        }
        .stat-box h3 {
            margin: 0;
            font-size: 24px;
            color: #28a745;
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
        .chart-placeholder {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border: 1px dashed #ddd;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Rapport Hebdomadaire</h1>
        <p><strong>Projet:</strong> {{ $project->name }}</p>
        <p><strong>Période:</strong> {{ $startDate->format('d/m/Y') }} au {{ $endDate->format('d/m/Y') }}</p>
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
            <h3>{{ number_format($data['totalOvertime'], 1) }}h</h3>
            <p>Heures supplémentaires</p>
        </div>
        <div class="stat-box">
            <h3>{{ number_format($data['totalExpenses'], 2) }} €</h3>
            <p>Dépenses totales</p>
        </div>
        <div class="stat-box">
            <h3>{{ $data['tasks']->count() }}</h3>
            <p>Tâches</p>
        </div>
        <div class="stat-box">
            <h3>{{ $data['overdueTasks'] }}</h3>
            <p>Tâches en retard</p>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Résumé des pointages</div>
        <table>
            <thead>
                <tr>
                    <th>Employé</th>
                    <th>Jours présents</th>
                    <th>Heures totales</th>
                    <th>Heures sup.</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $employeeStats = $data['attendances']->where('is_present', true)->groupBy('employee_id')->map(function($attendances) {
                        return [
                            'employee' => $attendances->first()->employee,
                            'days' => $attendances->count(),
                            'hours' => $attendances->sum('hours_worked'),
                            'overtime' => $attendances->sum('overtime_hours'),
                        ];
                    });
                @endphp
                @foreach($employeeStats as $stat)
                    <tr>
                        <td>{{ $stat['employee']->full_name ?? 'N/A' }}</td>
                        <td>{{ $stat['days'] }}</td>
                        <td>{{ number_format($stat['hours'], 1) }}h</td>
                        <td>{{ number_format($stat['overtime'], 1) }}h</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Dépenses par type</div>
        @if($data['expensesByType']->count() > 0)
            <table>
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Montant</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['expensesByType'] as $type => $amount)
                        <tr>
                            <td>{{ ucfirst($type) }}</td>
                            <td>{{ number_format($amount, 2) }} €</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th>Total</th>
                        <th>{{ number_format($data['totalExpenses'], 2) }} €</th>
                    </tr>
                </tfoot>
            </table>
        @else
            <p>Aucune dépense enregistrée pour cette période.</p>
        @endif
    </div>

    <div class="section">
        <div class="section-title">Tâches par statut</div>
        @if($data['tasksByStatus']->count() > 0)
            <table>
                <thead>
                    <tr>
                        <th>Statut</th>
                        <th>Nombre</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['tasksByStatus'] as $status => $count)
                        <tr>
                            <td>{{ ucfirst(str_replace('_', ' ', $status)) }}</td>
                            <td>{{ $count }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p>Aucune tâche pour cette période.</p>
        @endif
    </div>

    <div class="section">
        <div class="section-title">Évolution de l'avancement</div>
        <div class="chart-placeholder">
            <p><strong>Évolution sur la période:</strong></p>
            @foreach($data['progressEvolution'] as $evolution)
                <p>{{ $evolution['date'] }}: {{ $evolution['progress'] }}%</p>
            @endforeach
        </div>
    </div>

    <div class="footer">
        <p>Rapport généré le {{ now()->format('d/m/Y à H:i') }}</p>
        <p>{{ $project->company->name ?? 'Chantix' }} - Tous droits réservés</p>
    </div>
</body>
</html>

