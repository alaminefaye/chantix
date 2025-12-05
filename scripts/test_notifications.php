<?php

/**
 * Script de test rapide pour les notifications push
 * 
 * Usage: php artisan tinker < scripts/test_notifications.php
 * Ou: php scripts/test_notifications.php (si exécuté directement)
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🧪 Test des Notifications Push\n";
echo "==============================\n\n";

// 1. Vérifier la configuration Firebase
echo "1️⃣ Vérification de la configuration Firebase...\n";
$firebasePath = config('services.firebase.credentials_path');
echo "   Chemin configuré: $firebasePath\n";

if (file_exists($firebasePath)) {
    echo "   ✅ Fichier Firebase credentials trouvé\n";
} else {
    echo "   ❌ Fichier Firebase credentials NON trouvé\n";
    exit(1);
}

// 2. Vérifier la table fcm_tokens
echo "\n2️⃣ Vérification de la table fcm_tokens...\n";
try {
    $tokenCount = \App\Models\FcmToken::count();
    $activeTokens = \App\Models\FcmToken::where('is_active', true)->count();
    echo "   Total tokens: $tokenCount\n";
    echo "   Tokens actifs: $activeTokens\n";
    
    if ($activeTokens > 0) {
        echo "   ✅ Des tokens FCM sont enregistrés\n";
    } else {
        echo "   ⚠️  Aucun token FCM actif. Lancez l'app Flutter et connectez-vous.\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Erreur: " . $e->getMessage() . "\n";
    echo "   💡 Exécutez: php artisan migrate\n";
    exit(1);
}

// 3. Tester l'initialisation du service
echo "\n3️⃣ Test d'initialisation du service PushNotificationService...\n";
try {
    $service = new \App\Services\PushNotificationService();
    echo "   ✅ Service initialisé avec succès\n";
} catch (\Exception $e) {
    echo "   ❌ Erreur lors de l'initialisation: " . $e->getMessage() . "\n";
    exit(1);
}

// 4. Vérifier les utilisateurs
echo "\n4️⃣ Vérification des utilisateurs...\n";
$users = \App\Models\User::whereNotNull('current_company_id')->get();
echo "   Utilisateurs avec entreprise: " . $users->count() . "\n";

if ($users->count() > 0) {
    $user = $users->first();
    echo "   Utilisateur de test: {$user->name} (ID: {$user->id})\n";
    echo "   Entreprise: {$user->current_company_id}\n";
    
    // 5. Test d'envoi (optionnel)
    echo "\n5️⃣ Test d'envoi de notification (optionnel)...\n";
    echo "   Voulez-vous envoyer une notification de test ? (y/n): ";
    
    // Pour un script interactif, vous pouvez utiliser readline si disponible
    if (function_exists('readline')) {
        $response = trim(readline());
        if (strtolower($response) === 'y') {
            try {
                $result = $service->sendToUser(
                    $user->id,
                    '🧪 Test de Notification',
                    'Ceci est un message de test pour vérifier que les notifications fonctionnent !',
                    ['type' => 'test', 'timestamp' => now()->toIso8601String()]
                );
                
                if ($result) {
                    echo "   ✅ Notification envoyée avec succès\n";
                } else {
                    echo "   ⚠️  Notification non envoyée (peut-être aucun token actif)\n";
                }
            } catch (\Exception $e) {
                echo "   ❌ Erreur lors de l'envoi: " . $e->getMessage() . "\n";
            }
        }
    } else {
        echo "   (Mode non-interactif - passez cette étape)\n";
    }
} else {
    echo "   ⚠️  Aucun utilisateur avec entreprise trouvé\n";
}

// 6. Résumé
echo "\n📊 Résumé\n";
echo "==========\n";
echo "✅ Configuration Firebase: OK\n";
echo "✅ Table fcm_tokens: OK\n";
echo "✅ Service PushNotificationService: OK\n";
echo "\n💡 Prochaines étapes:\n";
echo "   1. Lancez l'app Flutter et connectez-vous\n";
echo "   2. Vérifiez que le token FCM est enregistré\n";
echo "   3. Créez ou modifiez un matériau via l'API\n";
echo "   4. Vérifiez la réception de la notification\n";
echo "\n📖 Consultez GUIDE_TEST_NOTIFICATIONS.md pour plus de détails\n";

