#!/bin/bash

# Script de déploiement pour vider les caches Laravel et PHP
# À exécuter sur le serveur après chaque déploiement

echo "🚀 Déploiement en cours..."

# Aller dans le répertoire du projet
cd "$(dirname "$0")"

# Vérifier que nous sommes dans le bon répertoire
if [ ! -f "artisan" ]; then
    echo "❌ Erreur: Fichier artisan non trouvé. Êtes-vous dans le bon répertoire?"
    exit 1
fi

# Vider tous les caches Laravel
echo "📦 Vidage des caches Laravel..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear

# Vider le cache OPcache de PHP (si disponible)
echo "📦 Vidage du cache OPcache..."
if [ -n "$(php -r 'if(function_exists("opcache_reset")) echo "opcache";')" ]; then
    php -r "if(function_exists('opcache_reset')) opcache_reset();"
    echo "✓ OPcache vidé"
else
    echo "⚠ OPcache non disponible"
fi

# Vider le cache APCu (si disponible)
if [ -n "$(php -r 'if(function_exists("apcu_clear_cache")) echo "apcu";')" ]; then
    php -r "if(function_exists('apcu_clear_cache')) apcu_clear_cache();"
    echo "✓ APCu vidé"
fi

# Nettoyer les fichiers compilés
echo "🧹 Nettoyage des fichiers compilés..."
rm -rf bootstrap/cache/*.php
rm -rf storage/framework/cache/data/*
rm -rf storage/framework/views/*.php

# Réexécuter les migrations si nécessaire (optionnel)
# php artisan migrate --force

# Optimiser pour la production (optionnel, à utiliser seulement en production)
if [ "$1" == "production" ]; then
    echo "⚡ Optimisation pour la production..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
    echo "✓ Caches de production créés"
else
    echo "ℹ Mode développement: caches non optimisés"
fi

# Vérifier les permissions
echo "🔐 Vérification des permissions..."
chmod -R 775 storage bootstrap/cache 2>/dev/null || true
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

echo "✅ Déploiement terminé!"
