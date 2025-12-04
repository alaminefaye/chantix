<?php
/**
 * Script de test pour vérifier l'accès aux invitations
 * À exécuter directement sur le serveur : php test-invitation-access.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Invitation;
use App\Models\Company;
use Illuminate\Support\Facades\DB;

echo "🔍 Test d'accès aux invitations\n";
echo str_repeat("=", 50) . "\n\n";

// 1. Trouver l'utilisateur
$userEmail = 'aminefye@gmail.com'; // Remplacez par votre email
$user = User::where('email', $userEmail)->first();

if (!$user) {
    echo "❌ Utilisateur non trouvé avec l'email: $userEmail\n";
    exit(1);
}

echo "✅ Utilisateur trouvé:\n";
echo "   - ID: {$user->id}\n";
echo "   - Nom: {$user->name}\n";
echo "   - Email: {$user->email}\n";
echo "   - Super Admin: " . ($user->is_super_admin ? 'Oui' : 'Non') . "\n";
echo "   - Current Company ID: " . ($user->current_company_id ?? 'NULL') . "\n\n";

// 2. Vérifier les invitations
$invitations = Invitation::where('invited_by', $user->id)->get();

echo "📧 Invitations créées par cet utilisateur: " . $invitations->count() . "\n";
foreach ($invitations as $invitation) {
    echo "\n   Invitation ID: {$invitation->id}\n";
    echo "   - Email: {$invitation->email}\n";
    echo "   - Company ID: {$invitation->company_id}\n";
    echo "   - Invited By: {$invitation->invited_by}\n";
    echo "   - Status: {$invitation->status}\n";
    
    // Vérifier l'accès
    $company = Company::find($invitation->company_id);
    if ($company) {
        echo "   - Company: {$company->name}\n";
        
        // Test 1: Vérifier si l'utilisateur appartient à l'entreprise
        $belongsToCompany = $user->companies()->where('companies.id', $company->id)->exists();
        echo "   - Appartient à l'entreprise: " . ($belongsToCompany ? 'Oui' : 'Non') . "\n";
        
        // Test 2: Vérifier le rôle admin
        $isAdmin = $user->hasRoleInCompany('admin', $company->id);
        echo "   - Est admin: " . ($isAdmin ? 'Oui' : 'Non') . "\n";
        
        // Test 3: Vérifier si c'est le créateur
        $isCreator = ($invitation->invited_by == $user->id);
        echo "   - Est créateur: " . ($isCreator ? 'Oui' : 'Non') . "\n";
        
        // Test 4: Vérification directe dans la base de données
        $roleInDb = DB::table('company_user')
            ->join('roles', 'company_user.role_id', '=', 'roles.id')
            ->where('company_user.user_id', $user->id)
            ->where('company_user.company_id', $company->id)
            ->where('company_user.is_active', true)
            ->select('roles.name', 'roles.id')
            ->first();
        
        echo "   - Rôle dans DB: " . ($roleInDb ? $roleInDb->name . " (ID: {$roleInDb->id})" : 'Aucun') . "\n";
        
        // Résultat final
        $hasAccess = $isCreator || $isAdmin || $user->isSuperAdmin();
        echo "   - ✅ ACCÈS AUTORISÉ: " . ($hasAccess ? 'OUI' : 'NON') . "\n";
        
        if (!$hasAccess) {
            echo "   ⚠️  PROBLÈME: L'utilisateur devrait avoir accès mais ne l'a pas!\n";
        }
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "✅ Test terminé\n";

