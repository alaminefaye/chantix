<?php

/**
 * Script pour tester l'enregistrement d'un token FCM manuellement
 * 
 * Usage: php scripts/test_fcm_registration.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🧪 Test d'Enregistrement FCM Token\n";
echo "==================================\n\n";

// 1. Obtenir un utilisateur
$user = \App\Models\User::find(2);
if (!$user) {
    echo "❌ Utilisateur ID 2 non trouvé\n";
    exit(1);
}

echo "1️⃣ Utilisateur trouvé: {$user->name} (ID: {$user->id})\n";
echo "   Email: {$user->email}\n\n";

// 2. Créer un token Sanctum pour l'authentification
echo "2️⃣ Création d'un token Sanctum pour l'authentification...\n";
$token = $user->createToken('test-fcm-registration')->plainTextToken;
echo "   ✅ Token créé: " . substr($token, 0, 30) . "...\n\n";

// 3. Simuler l'enregistrement d'un token FCM
echo "3️⃣ Test d'enregistrement d'un token FCM...\n";

$testFcmToken = 'test_fcm_token_' . time();
$testData = [
    'token' => $testFcmToken,
    'device_id' => 'test_device_' . time(),
    'device_type' => 'android',
    'device_name' => 'Test Device via Script',
];

// Créer une requête simulée
$request = \Illuminate\Http\Request::create('/api/v1/fcm-tokens', 'POST', $testData);
$request->headers->set('Authorization', 'Bearer ' . $token);
$request->headers->set('Content-Type', 'application/json');
$request->headers->set('Accept', 'application/json');

// Authentifier l'utilisateur
auth()->setUser($user);

// Appeler le contrôleur
try {
    $controller = new \App\Http\Controllers\Api\FcmTokenController();
    $response = $controller->store($request);
    $responseData = json_decode($response->getContent(), true);
    
    if ($response->getStatusCode() == 200 || $response->getStatusCode() == 201) {
        echo "   ✅ Token FCM enregistré avec succès !\n";
        echo "   ID: {$responseData['data']['id']}\n";
        echo "   Token: " . substr($responseData['data']['token'], 0, 50) . "...\n";
        echo "   Device: {$responseData['data']['device_type']}\n";
    } else {
        echo "   ❌ Erreur: " . ($responseData['message'] ?? 'Erreur inconnue') . "\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n";
    echo "   Stack trace: " . $e->getTraceAsString() . "\n";
}

// 4. Vérifier dans la base de données
echo "\n4️⃣ Vérification dans la base de données...\n";
$fcmToken = \App\Models\FcmToken::where('user_id', $user->id)
    ->where('token', $testFcmToken)
    ->first();

if ($fcmToken) {
    echo "   ✅ Token trouvé dans la base de données\n";
    echo "   ID: {$fcmToken->id}\n";
    echo "   Actif: " . ($fcmToken->is_active ? 'Oui' : 'Non') . "\n";
    echo "   Créé: {$fcmToken->created_at}\n";
} else {
    echo "   ❌ Token non trouvé dans la base de données\n";
}

// 5. Compter les tokens actifs pour cet utilisateur
echo "\n5️⃣ Tokens actifs pour l'utilisateur {$user->id}...\n";
$activeTokens = \App\Models\FcmToken::where('user_id', $user->id)
    ->where('is_active', true)
    ->get();

echo "   Nombre: " . $activeTokens->count() . "\n";
if ($activeTokens->count() > 0) {
    echo "   Tokens:\n";
    foreach ($activeTokens as $token) {
        echo "   - ID: {$token->id}, Device: {$token->device_type}, Créé: {$token->created_at}\n";
    }
}

// 6. Instructions pour tester depuis Flutter
echo "\n📱 Instructions pour tester depuis Flutter:\n";
echo "==========================================\n";
echo "1. Lancez l'app Flutter\n";
echo "2. Connectez-vous avec: {$user->email}\n";
echo "3. Observez les logs dans la console Flutter\n";
echo "4. Cherchez ces messages:\n";
echo "   - '🔄 Tentative d'enregistrement du token FCM...'\n";
echo "   - '✅ Auth token trouvé: ...'\n";
echo "   - '📤 Envoi de la requête à /v1/fcm-tokens...'\n";
echo "   - '✅ FCM token registered successfully'\n";
echo "\n5. Vérifiez les logs backend:\n";
echo "   tail -f storage/logs/laravel.log | grep -i fcm\n";
echo "\n6. Vérifiez dans la base de données:\n";
echo "   php artisan tinker\n";
echo "   >>> \\App\\Models\\FcmToken::where('user_id', 2)->where('is_active', true)->count()\n";

echo "\n✅ Test terminé\n";

