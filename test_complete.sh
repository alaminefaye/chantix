#!/bin/bash

# Test complet pour identifier le problème

EMAIL="aminefaye@gmail.com"

echo "=========================================="
echo "TEST COMPLET - Diagnostic"
echo "=========================================="
echo ""

echo "1. Test accessibleByUser (déjà fait - devrait retourner 2 projets)"
echo ""

echo "2. Test de l'endpoint API Dashboard..."
php artisan tinker --execute="
\$user = App\Models\User::where('email', '$EMAIL')->first();
if (\$user) {
    Auth::login(\$user);
    \$companyId = \$user->current_company_id;
    
    // Simuler l'appel au dashboard
    \$baseQuery = App\Models\Project::accessibleByUser(\$user, \$companyId);
    \$totalProjects = (clone \$baseQuery)->count();
    \$activeProjects = (clone \$baseQuery)->where('status', 'en_cours')->count();
    
    echo \"Dashboard - Total projets: \$totalProjects\n\";
    echo \"Dashboard - Projets actifs: \$activeProjects\n\";
    
    // Vérifier les projets retournés
    \$projects = (clone \$baseQuery)->get();
    echo \"Dashboard - IDs des projets: \" . \$projects->pluck('id')->implode(', ') . \"\n\";
}
"

echo ""
echo "3. Vérification si l'utilisateur voit les projets via la relation Eloquent..."
php artisan tinker --execute="
\$user = App\Models\User::where('email', '$EMAIL')->first();
if (\$user) {
    // Vérifier la relation projects()
    \$projectsViaRelation = \$user->projects()->get();
    echo \"Projets via relation user->projects(): \" . \$projectsViaRelation->count() . \"\n\";
    echo \"IDs: \" . \$projectsViaRelation->pluck('id')->implode(', ') . \"\n\";
    
    // Vérifier tous les projets de l'entreprise
    \$allProjects = App\Models\Project::where('company_id', \$user->current_company_id)->get();
    echo \"\nTous les projets de l'entreprise: \" . \$allProjects->count() . \"\n\";
    echo \"IDs: \" . \$allProjects->pluck('id')->implode(', ') . \"\n\";
}
"

echo ""
echo "4. Vérification des logs récents de l'API..."
echo ""
echo "Recherche des logs 'API Projects' ou 'accessibleByUser' dans les 100 dernières lignes:"
tail -n 100 storage/logs/laravel.log | grep -E "API Projects|accessibleByUser" | tail -n 20

echo ""
echo "=========================================="
echo "RÉSUMÉ"
echo "=========================================="
echo ""
echo "Si accessibleByUser retourne 2 projets mais l'app mobile en montre plus,"
echo "le problème vient probablement de:"
echo "1. Un cache dans l'application mobile"
echo "2. L'API n'utilise pas accessibleByUser (peu probable)"
echo "3. Le dashboard utilise une autre méthode"
echo ""
