#!/bin/bash

# Script pour corriger les permissions des logs et s'assurer qu'ils sont accessibles

echo "🔧 Correction des permissions des logs..."
echo ""

cd "$(dirname "$0")" || exit 1

# 1. Créer le répertoire logs s'il n'existe pas
if [ ! -d "storage/logs" ]; then
    mkdir -p storage/logs
    echo "✅ Répertoire storage/logs créé"
fi

# 2. Créer le fichier laravel.log s'il n'existe pas
if [ ! -f "storage/logs/laravel.log" ]; then
    touch storage/logs/laravel.log
    echo "✅ Fichier storage/logs/laravel.log créé"
fi

# 3. Corriger les permissions
echo "🔐 Correction des permissions..."
chmod -R 775 storage/logs
chmod -R 775 storage/framework
chmod -R 775 bootstrap/cache

# Vérifier le propriétaire (si vous êtes root ou avez les droits)
if [ "$EUID" -eq 0 ]; then
    # Si vous êtes root, définir le bon propriétaire
    # Remplacez www-data par l'utilisateur web de votre serveur
    chown -R www-data:www-data storage/logs 2>/dev/null || echo "⚠️  Impossible de changer le propriétaire (peut-être pas root)"
fi

echo "✅ Permissions corrigées"
echo ""

# 4. Tester l'écriture
echo "📝 Test d'écriture dans les logs..."
php -r "
    \$logFile = __DIR__ . '/storage/logs/laravel.log';
    \$testMessage = '[' . date('Y-m-d H:i:s') . '] TEST: Écriture dans les logs fonctionne' . PHP_EOL;
    if (file_put_contents(\$logFile, \$testMessage, FILE_APPEND)) {
        echo '✅ Écriture dans les logs réussie' . PHP_EOL;
    } else {
        echo '❌ Échec de l\'écriture dans les logs' . PHP_EOL;
        echo '   Vérifiez les permissions du fichier: ' . \$logFile . PHP_EOL;
    }
"

echo ""
echo "✅ Correction terminée"
echo ""
echo "📋 Pour vérifier les logs:"
echo "   tail -f storage/logs/laravel.log"

