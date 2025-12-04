#!/bin/bash

# Script pour vérifier que les fichiers ont bien été uploadés sur le serveur

echo "🔍 Vérification des fichiers sur le serveur..."
echo ""

cd "$(dirname "$0")" || exit 1

# 1. Vérifier que les fichiers modifiés existent
echo "📁 Vérification des fichiers modifiés:"
echo ""

FILES_TO_CHECK=(
    "app/Http/Controllers/InvitationController.php"
    "app/Models/User.php"
)

for file in "${FILES_TO_CHECK[@]}"; do
    if [ -f "$file" ]; then
        echo "✅ $file existe"
        
        # Vérifier les modifications clés
        if [ "$file" == "app/Http/Controllers/InvitationController.php" ]; then
            if grep -q "PRIORITÉ 1: Si l'utilisateur a créé l'invitation" "$file"; then
                echo "   ✅ Contient la logique de priorité au créateur"
            else
                echo "   ❌ Ne contient PAS la logique de priorité au créateur"
            fi
        fi
        
        if [ "$file" == "app/Models/User.php" ]; then
            if grep -q "DB::table('company_user')" "$file"; then
                echo "   ✅ Contient la requête directe DB"
            else
                echo "   ❌ Ne contient PAS la requête directe DB"
            fi
        fi
    else
        echo "❌ $file N'EXISTE PAS"
    fi
    echo ""
done

# 2. Vérifier les permissions des logs
echo "📝 Vérification des permissions des logs:"
if [ -d "storage/logs" ]; then
    if [ -w "storage/logs" ]; then
        echo "✅ storage/logs est accessible en écriture"
    else
        echo "❌ storage/logs n'est PAS accessible en écriture"
        echo "   Exécutez: chmod -R 775 storage/logs"
    fi
    
    if [ -f "storage/logs/laravel.log" ]; then
        echo "✅ storage/logs/laravel.log existe"
        echo "   Taille: $(du -h storage/logs/laravel.log | cut -f1)"
        echo "   Dernière modification: $(stat -f "%Sm" storage/logs/laravel.log 2>/dev/null || stat -c "%y" storage/logs/laravel.log 2>/dev/null)"
    else
        echo "⚠️  storage/logs/laravel.log n'existe pas encore"
    fi
else
    echo "❌ storage/logs n'existe pas"
fi
echo ""

# 3. Vérifier la version PHP
echo "🐘 Version PHP:"
php -v | head -n 1
echo ""

# 4. Vérifier que les routes sont bien cachées
echo "🛣️  Vérification des routes:"
if [ -f "bootstrap/cache/routes-v7.php" ]; then
    echo "✅ Routes cachées existent"
    echo "   Date de modification: $(stat -f "%Sm" bootstrap/cache/routes-v7.php 2>/dev/null || stat -c "%y" bootstrap/cache/routes-v7.php 2>/dev/null)"
    echo "   ⚠️  Les routes sont en cache - exécutez: php artisan route:clear"
else
    echo "⚠️  Routes non cachées"
fi
echo ""

# 5. Vérifier la base de données
echo "🗄️  Test de connexion à la base de données:"
php artisan tinker --execute="
    try {
        \$user = \App\Models\User::where('email', 'aminefye@gmail.com')->first();
        if (\$user) {
            echo '✅ Utilisateur trouvé: ' . \$user->name . ' (ID: ' . \$user->id . ')' . PHP_EOL;
            \$invitations = \App\Models\Invitation::where('invited_by', \$user->id)->get();
            echo '✅ Invitations créées: ' . \$invitations->count() . PHP_EOL;
            foreach (\$invitations as \$inv) {
                echo '   - ID: ' . \$inv->id . ', Email: ' . \$inv->email . ', Invited By: ' . \$inv->invited_by . PHP_EOL;
            }
        } else {
            echo '❌ Utilisateur non trouvé' . PHP_EOL;
        }
    } catch (\Exception \$e) {
        echo '❌ Erreur: ' . \$e->getMessage() . PHP_EOL;
    }
" 2>&1 | head -20

echo ""
echo "✅ Vérification terminée"

