#!/bin/bash

# Script pour corriger les assignations de projets pour aminefaye@gmail.com

EMAIL="aminefaye@gmail.com"

echo "=========================================="
echo "CORRECTION DES PROJETS POUR: $EMAIL"
echo "=========================================="
echo ""

echo "1. Correction automatique des assignations..."
php artisan user:fix-project-assignments

echo ""
echo "2. Si vous voulez assigner un projet spécifique, utilisez cette commande:"
echo "   php artisan tinker --execute=\""
echo "   \\\$user = App\Models\User::where('email', '$EMAIL')->first();"
echo "   \\\$projectId = 1; // Remplacez par l'ID du projet"
echo "   if (\\\$user) {"
echo "       DB::table('project_user')->where('user_id', \\\$user->id)->delete();"
echo "       DB::table('project_user')->insert(["
echo "           'user_id' => \\\$user->id,"
echo "           'project_id' => \\\$projectId,"
echo "           'created_at' => now(),"
echo "           'updated_at' => now(),"
echo "       ]);"
echo "       echo \\\"✅ Projet assigné\\\";"
echo "   }"
echo "   \""

echo ""
echo "=========================================="
echo "CORRECTION TERMINÉE"
echo "=========================================="
