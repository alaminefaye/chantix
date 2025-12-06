<?php

/**
 * Script de test pour vérifier l'envoi de notifications push
 * 
 * Usage: php artisan tinker
 * >>> require 'scripts/test_push_notification.php';
 * >>> testPushNotification($userId);
 */

use App\Services\PushNotificationService;
use App\Models\User;
use App\Models\FcmToken;

function testPushNotification($userId) {
    echo "\n🔍 Test de notification push pour l'utilisateur ID: {$userId}\n";
    echo str_repeat("=", 60) . "\n\n";
    
    // Vérifier l'utilisateur
    $user = User::find($userId);
    if (!$user) {
        echo "❌ Utilisateur {$userId} non trouvé\n";
        return;
    }
    
    echo "✅ Utilisateur trouvé: {$user->name} ({$user->email})\n\n";
    
    // Vérifier les tokens FCM
    $tokens = FcmToken::where('user_id', $userId)->get();
    echo "📱 Tokens FCM trouvés: " . $tokens->count() . "\n";
    
    if ($tokens->isEmpty()) {
        echo "❌ Aucun token FCM trouvé pour cet utilisateur\n";
        echo "💡 L'utilisateur doit se connecter à l'application mobile pour enregistrer un token\n";
        return;
    }
    
    foreach ($tokens as $token) {
        echo "  - Token ID: {$token->id}\n";
        echo "    Actif: " . ($token->is_active ? 'Oui ✅' : 'Non ❌') . "\n";
        echo "    Type: {$token->device_type}\n";
        echo "    Token: " . substr($token->token, 0, 50) . "...\n";
        echo "    Dernière utilisation: " . ($token->last_used_at ? $token->last_used_at : 'Jamais') . "\n\n";
    }
    
    // Vérifier les tokens actifs
    $activeTokens = FcmToken::where('user_id', $userId)
        ->where('is_active', true)
        ->get();
    
    if ($activeTokens->isEmpty()) {
        echo "⚠️ Aucun token actif trouvé. Les tokens doivent être actifs pour recevoir des notifications push.\n";
        echo "💡 Vérifiez que les tokens sont bien marqués comme actifs (is_active = 1)\n";
        return;
    }
    
    echo "✅ Tokens actifs: " . $activeTokens->count() . "\n\n";
    
    // Tester l'envoi
    echo "📤 Test d'envoi de notification push...\n";
    try {
        $pushService = new PushNotificationService();
        $result = $pushService->sendToUser(
            $userId,
            'Test de notification',
            'Ceci est un test de notification push depuis le serveur',
            ['test' => true, 'timestamp' => now()->toDateTimeString()]
        );
        
        if ($result) {
            echo "✅ Notification push envoyée avec succès!\n";
        } else {
            echo "❌ Échec de l'envoi de la notification push\n";
            echo "💡 Vérifiez les logs Laravel pour plus de détails\n";
        }
    } catch (\Exception $e) {
        echo "❌ Erreur lors de l'envoi: " . $e->getMessage() . "\n";
        echo "📋 Trace: " . $e->getTraceAsString() . "\n";
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
}

// Si exécuté directement
if (php_sapi_name() === 'cli' && isset($argv[1])) {
    require __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    
    $userId = (int) $argv[1];
    testPushNotification($userId);
}

