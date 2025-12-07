#!/bin/bash

# Script de vérification et correction des routes pour le check-in

echo "🔍 Vérification des routes check-in..."
echo ""

# Vérifier que la route existe
echo "1. Liste des routes check-in :"
php artisan route:list | grep -i "check-in"
echo ""

# Vérifier le contenu du fichier routes/api.php
echo "2. Vérification du fichier routes/api.php :"
if grep -q "projects/{projectId}" routes/api.php && grep -q "attendances/check-in" routes/api.php; then
    echo "✅ La route est correctement configurée avec {projectId}"
else
    echo "❌ ERREUR: La route n'est pas correctement configurée!"
    echo "   Elle doit contenir: projects/{projectId} et attendances/check-in"
fi
echo ""

# Vider les caches
echo "3. Vidage des caches..."
php artisan route:clear
php artisan config:clear
php artisan cache:clear
php artisan view:clear
echo "✅ Caches vidés"
echo ""

# Vérifier à nouveau les routes
echo "4. Vérification finale des routes :"
php artisan route:list | grep -i "check-in"
echo ""

echo "✅ Vérification terminée!"
echo ""
echo "Si la route n'apparaît pas, vérifiez que:"
echo "  - Le fichier routes/api.php contient bien: Route::prefix('projects/{projectId}')"
echo "  - Le contrôleur AttendanceController existe et a la méthode checkIn"
echo "  - Vous êtes dans le bon répertoire du projet"
