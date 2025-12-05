<?php

/**
 * Script de diagnostic pour les tokens FCM
 * 
 * Usage: php scripts/diagnostic_fcm.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔍 Diagnostic FCM - Tokens\n";
echo "==========================\n\n";

// 1. Vérifier la table
echo "1️⃣ Vérification de la table fcm_tokens...\n";
try {
    $schema = \Illuminate\Support\Facades\Schema::hasTable('fcm_tokens');
    if ($schema) {
        echo "   ✅ Table fcm_tokens existe\n";
        
        $columns = \Illuminate\Support\Facades\DB::select("PRAGMA table_info(fcm_tokens)");
        if (empty($columns)) {
            // MySQL
            $columns = \Illuminate\Support\Facades\DB::select("DESCRIBE fcm_tokens");
        }
        echo "   Colonnes: " . count($columns) . "\n";
    } else {
        echo "   ❌ Table fcm_tokens n'existe pas\n";
        echo "   💡 Exécutez: php artisan migrate\n";
        exit(1);
    }
} catch (\Exception $e) {
    echo "   ❌ Erreur: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Vérifier les tokens
echo "\n2️⃣ Tokens enregistrés...\n";
$allTokens = \App\Models\FcmToken::all();
$activeTokens = \App\Models\FcmToken::where('is_active', true)->get();

echo "   Total: " . $allTokens->count() . "\n";
echo "   Actifs: " . $activeTokens->count() . "\n";

if ($activeTokens->count() > 0) {
    echo "\n   Tokens actifs:\n";
    foreach ($activeTokens as $token) {
        echo "   - ID: {$token->id}, User: {$token->user_id}, Device: {$token->device_type}\n";
        echo "     Token: " . substr($token->token, 0, 50) . "...\n";
        echo "     Créé: {$token->created_at}\n";
    }
} else {
    echo "   ⚠️  Aucun token actif\n";
}

// 3. Vérifier les utilisateurs avec tokens
echo "\n3️⃣ Utilisateurs avec tokens...\n";
$usersWithTokens = \App\Models\User::whereHas('fcmTokens', function($query) {
    $query->where('is_active', true);
})->get();

echo "   Nombre: " . $usersWithTokens->count() . "\n";
if ($usersWithTokens->count() > 0) {
    foreach ($usersWithTokens as $user) {
        $tokensCount = $user->activeFcmTokens()->count();
        echo "   - {$user->name} (ID: {$user->id}): {$tokensCount} token(s)\n";
    }
}

// 4. Tester l'endpoint API
echo "\n4️⃣ Test de l'endpoint API...\n";
echo "   Route: POST /api/v1/fcm-tokens\n";

// Créer un utilisateur de test ou utiliser le premier
$testUser = \App\Models\User::first();
if ($testUser) {
    // Créer un token Sanctum pour tester
    $token = $testUser->createToken('test-token')->plainTextToken;
    echo "   Token de test créé pour: {$testUser->name}\n";
    echo "   Token: " . substr($token, 0, 20) . "...\n";
    echo "\n   💡 Testez avec:\n";
    echo "   curl -X POST http://localhost:8000/api/v1/fcm-tokens \\\n";
    echo "     -H \"Authorization: Bearer $token\" \\\n";
    echo "     -H \"Content-Type: application/json\" \\\n";
    echo "     -d '{\"token\":\"test_fcm_token_123\",\"device_type\":\"android\"}'\n";
}

// 5. Vérifier les logs récents
echo "\n5️⃣ Vérification des logs récents...\n";
$logPath = storage_path('logs/laravel.log');
if (file_exists($logPath)) {
    $lines = file($logPath);
    $recentLines = array_slice($lines, -50); // Dernières 50 lignes
    
    $fcmLogs = array_filter($recentLines, function($line) {
        return stripos($line, 'fcm') !== false || 
               stripos($line, 'token') !== false ||
               stripos($line, 'notification') !== false;
    });
    
    if (count($fcmLogs) > 0) {
        echo "   Logs récents liés à FCM:\n";
        foreach (array_slice($fcmLogs, -10) as $log) {
            echo "   " . trim($log) . "\n";
        }
    } else {
        echo "   Aucun log récent lié à FCM\n";
    }
} else {
    echo "   Fichier de log non trouvé\n";
}

// 6. Vérifier la configuration API
echo "\n6️⃣ Configuration API Flutter...\n";
echo "   URL de base configurée dans Flutter:\n";
echo "   Vérifiez: chantix_app/lib/config/api_config.dart\n";
echo "   L'URL doit pointer vers: https://chantix.universaltechnologiesafrica.com/api\n";

echo "\n📊 Résumé\n";
echo "==========\n";
echo "✅ Table fcm_tokens: " . ($schema ? "OK" : "NOK") . "\n";
echo "✅ Tokens enregistrés: " . $allTokens->count() . "\n";
echo "✅ Tokens actifs: " . $activeTokens->count() . "\n";
echo "✅ Utilisateurs avec tokens: " . $usersWithTokens->count() . "\n";

if ($activeTokens->count() == 0) {
    echo "\n💡 Problèmes possibles:\n";
    echo "   1. L'app Flutter n'a pas encore enregistré le token\n";
    echo "   2. L'utilisateur n'est pas connecté dans l'app\n";
    echo "   3. L'API endpoint n'est pas accessible depuis l'app\n";
    echo "   4. Erreur lors de l'enregistrement (vérifier les logs)\n";
    echo "\n🔧 Actions à faire:\n";
    echo "   1. Vérifier les logs Flutter pour les erreurs\n";
    echo "   2. Vérifier que l'URL de l'API est correcte\n";
    echo "   3. Tester l'endpoint API manuellement avec curl\n";
    echo "   4. Vérifier les logs Laravel pour les erreurs\n";
}

