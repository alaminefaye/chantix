#!/bin/bash

# Script pour vider le cache des routes Laravel
cd "$(dirname "$0")"

echo "Vidage du cache des routes Laravel..."

# Vider le cache des routes
php artisan route:clear 2>/dev/null || echo "⚠️  Impossible d'exécuter route:clear (PHP non disponible ou cache déjà vidé)"

# Vider le cache de configuration
php artisan config:clear 2>/dev/null || echo "⚠️  Impossible d'exécuter config:clear"

# Vider le cache général
php artisan cache:clear 2>/dev/null || echo "⚠️  Impossible d'exécuter cache:clear"

# Supprimer manuellement les fichiers de cache s'ils existent
rm -f bootstrap/cache/routes*.php 2>/dev/null
rm -f bootstrap/cache/config.php 2>/dev/null

echo "✅ Cache vidé (si PHP était disponible)"
echo ""
echo "Pour vider complètement le cache, exécutez manuellement:"
echo "  php artisan route:clear"
echo "  php artisan config:clear"
echo "  php artisan cache:clear"











