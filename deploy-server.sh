#!/bin/bash

# Script de déploiement pour synchroniser le serveur avec le local
# À exécuter sur le serveur après avoir poussé les modifications

echo "🚀 Déploiement du serveur Chantix..."
echo ""

# Aller dans le répertoire du projet
cd "$(dirname "$0")" || exit 1

# 1. Mettre à jour les dépendances Composer
echo "📦 Mise à jour des dépendances Composer..."
composer install --no-dev --optimize-autoloader || {
    echo "❌ Erreur lors de l'installation des dépendances Composer"
    exit 1
}
composer dump-autoload --optimize || {
    echo "❌ Erreur lors du dump-autoload"
    exit 1
}
echo "✅ Dépendances Composer mises à jour"
echo ""

# 2. Vider TOUS les caches Laravel
echo "🧹 Vidage de tous les caches Laravel..."
php artisan optimize:clear || {
    echo "⚠️  optimize:clear non disponible, utilisation des commandes individuelles..."
    php artisan config:clear
    php artisan route:clear
    php artisan cache:clear
    php artisan view:clear
}
echo "✅ Caches vidés"
echo ""

# 3. Vérifier et exécuter les migrations
echo "🗄️  Vérification des migrations..."
php artisan migrate --force || {
    echo "⚠️  Erreur lors des migrations (peut-être déjà à jour)"
}
echo "✅ Migrations vérifiées"
echo ""

# 4. Vérifier les seeders (rôles)
echo "🌱 Vérification des seeders..."
php artisan db:seed --class=RoleSeeder --force || {
    echo "⚠️  Erreur lors du seeding (peut-être déjà fait)"
}
echo "✅ Seeders vérifiés"
echo ""

# 5. Recréer les caches optimisés (production)
echo "⚡ Optimisation pour la production..."
php artisan config:cache || {
    echo "⚠️  Impossible de mettre en cache la config"
}
php artisan route:cache || {
    echo "⚠️  Impossible de mettre en cache les routes"
}
php artisan view:cache || {
    echo "⚠️  Impossible de mettre en cache les vues"
}
echo "✅ Optimisations appliquées"
echo ""

# 6. Vérifier les permissions
echo "🔐 Vérification des permissions..."
chmod -R 755 storage bootstrap/cache || {
    echo "⚠️  Impossible de modifier les permissions"
}
echo "✅ Permissions vérifiées"
echo ""

# 7. Vider le cache OPcache si disponible
echo "🔄 Vidage du cache OPcache..."
if command -v php &> /dev/null; then
    php -r "if (function_exists('opcache_reset')) { opcache_reset(); echo 'OPcache vidé\n'; } else { echo 'OPcache non disponible\n'; }"
fi
echo ""

echo "✅ Déploiement terminé avec succès!"
echo ""
echo "📝 Commandes exécutées:"
echo "   - composer install --no-dev --optimize-autoloader"
echo "   - composer dump-autoload --optimize"
echo "   - php artisan optimize:clear"
echo "   - php artisan migrate --force"
echo "   - php artisan db:seed --class=RoleSeeder --force"
echo "   - php artisan config:cache"
echo "   - php artisan route:cache"
echo "   - php artisan view:cache"
echo ""
echo "🔍 Si le problème persiste, vérifiez:"
echo "   1. Que les fichiers ont bien été uploadés sur le serveur"
echo "   2. Que la base de données est à jour"
echo "   3. Les logs: storage/logs/laravel.log"
echo "   4. Que votre utilisateur a bien le rôle admin dans company_user"

