#!/bin/bash

# Script de déploiement pour vider les caches Laravel
# À exécuter sur le serveur après chaque déploiement

echo "🚀 Déploiement en cours..."

# Aller dans le répertoire du projet
cd "$(dirname "$0")"

# Vider tous les caches Laravel
echo "📦 Vidage des caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear

# Optimiser pour la production (optionnel, à utiliser seulement en production)
if [ "$1" == "production" ]; then
    echo "⚡ Optimisation pour la production..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
fi

# Réexécuter les migrations si nécessaire (optionnel)
# php artisan migrate --force

echo "✅ Déploiement terminé!"
