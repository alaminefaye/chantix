<?php
/**
 * Test direct de la route pour voir si le contrôleur est appelé
 * À exécuter sur le serveur après avoir essayé d'accéder à l'invitation
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

echo "🔍 Vérification des logs après tentative d'accès\n";
echo str_repeat("=", 50) . "\n\n";

// Lire les 50 dernières lignes du log
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $lines = file($logFile);
    $recentLines = array_slice($lines, -50);
    
    echo "📝 Dernières 50 lignes du log:\n";
    echo str_repeat("-", 50) . "\n";
    
    $found = false;
    foreach ($recentLines as $line) {
        if (stripos($line, 'EDIT INVITATION') !== false || 
            stripos($line, 'SHOW INVITATION') !== false ||
            stripos($line, 'invitation') !== false) {
            echo $line;
            $found = true;
        }
    }
    
    if (!$found) {
        echo "⚠️  Aucun log d'invitation trouvé dans les 50 dernières lignes\n";
        echo "\nDernières lignes du log:\n";
        foreach (array_slice($recentLines, -10) as $line) {
            echo $line;
        }
    }
} else {
    echo "❌ Le fichier de log n'existe pas: $logFile\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "💡 Pour voir les logs en temps réel:\n";
echo "   tail -f storage/logs/laravel.log\n";
echo "\n💡 Pour tester l'accès, essayez:\n";
echo "   https://chantix.universaltechnologiesafrica.com/companies/1/invitations/1/edit\n";
echo "   Puis exécutez ce script à nouveau\n";

