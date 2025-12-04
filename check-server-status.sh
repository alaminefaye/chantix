#!/bin/bash

# Script pour vérifier l'état du serveur et diagnostiquer les problèmes

echo "🔍 Diagnostic du serveur Chantix..."
echo ""

cd "$(dirname "$0")" || exit 1

# 1. Vérifier la version PHP
echo "📋 Version PHP:"
php -v || echo "❌ PHP non disponible"
echo ""

# 2. Vérifier Composer
echo "📦 Version Composer:"
composer --version || echo "❌ Composer non disponible"
echo ""

# 3. Vérifier les migrations
echo "🗄️  État des migrations:"
php artisan migrate:status || echo "❌ Impossible de vérifier les migrations"
echo ""

# 4. Vérifier les rôles dans la base de données
echo "👥 Rôles dans la base de données:"
php artisan tinker --execute="echo 'Roles: ' . \App\Models\Role::count() . PHP_EOL; \App\Models\Role::all(['id', 'name'])->each(function(\$r) { echo '  - ' . \$r->name . ' (ID: ' . \$r->id . ')' . PHP_EOL; });" 2>/dev/null || echo "⚠️  Impossible de vérifier les rôles"
echo ""

# 5. Vérifier un utilisateur spécifique
echo "👤 Vérification d'un utilisateur (remplacez l'email):"
read -p "Email de l'utilisateur à vérifier: " user_email
if [ ! -z "$user_email" ]; then
    php artisan tinker --execute="
        \$user = \App\Models\User::where('email', '$user_email')->first();
        if (\$user) {
            echo 'Utilisateur trouvé: ' . \$user->name . PHP_EOL;
            echo 'ID: ' . \$user->id . PHP_EOL;
            echo 'Super Admin: ' . (\$user->is_super_admin ? 'Oui' : 'Non') . PHP_EOL;
            echo 'Current Company ID: ' . (\$user->current_company_id ?? 'Aucun') . PHP_EOL;
            echo 'Companies: ' . PHP_EOL;
            \$user->companies()->each(function(\$c) use (\$user) {
                \$pivot = \$user->companies()->where('companies.id', \$c->id)->first()->pivot;
                \$role = \App\Models\Role::find(\$pivot->role_id);
                echo '  - ' . \$c->name . ' (ID: ' . \$c->id . ') - Rôle: ' . (\$role ? \$role->name : 'Aucun') . PHP_EOL;
            });
        } else {
            echo 'Utilisateur non trouvé' . PHP_EOL;
        }
    " 2>/dev/null || echo "⚠️  Impossible de vérifier l'utilisateur"
fi
echo ""

# 6. Vérifier les invitations
echo "📧 Vérification des invitations:"
php artisan tinker --execute="
    \$invitations = \App\Models\Invitation::with('inviter')->take(5)->get();
    echo 'Dernières invitations:' . PHP_EOL;
    \$invitations->each(function(\$i) {
        echo '  - ID: ' . \$i->id . ' - Email: ' . \$i->email . ' - Créée par: ' . (\$i->inviter ? \$i->inviter->name : 'N/A') . ' (ID: ' . \$i->invited_by . ')' . PHP_EOL;
    });
" 2>/dev/null || echo "⚠️  Impossible de vérifier les invitations"
echo ""

# 7. Vérifier les caches
echo "💾 État des caches:"
ls -la bootstrap/cache/ 2>/dev/null || echo "⚠️  Répertoire cache non accessible"
echo ""

# 8. Vérifier les logs récents
echo "📝 Dernières erreurs dans les logs:"
tail -n 20 storage/logs/laravel.log 2>/dev/null | grep -i "error\|exception\|403" || echo "⚠️  Aucune erreur récente ou logs non accessibles"
echo ""

echo "✅ Diagnostic terminé"

