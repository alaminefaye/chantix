#!/bin/bash

# Test de l'API pour voir ce qu'elle retourne réellement

EMAIL="aminefaye@gmail.com"

echo "=========================================="
echo "TEST API POUR APPLICATION MOBILE"
echo "=========================================="
echo ""

echo "1. Vérification des projets assignés dans la base de données..."
php artisan tinker --execute="
\$user = App\Models\User::where('email', '$EMAIL')->first();
if (\$user) {
    \$assigned = DB::table('project_user')->where('user_id', \$user->id)->pluck('project_id')->toArray();
    echo \"Projets assignés: \" . (empty(\$assigned) ? 'AUCUN' : implode(', ', \$assigned)) . \"\n\";
    echo \"Nombre: \" . count(\$assigned) . \"\n\";
}
"

echo ""
echo "2. Test de accessibleByUser (ce que l'API devrait retourner)..."
php artisan tinker --execute="
\$user = App\Models\User::where('email', '$EMAIL')->first();
if (\$user && \$user->current_company_id) {
    \$query = App\Models\Project::accessibleByUser(\$user, \$user->current_company_id);
    \$projects = \$query->get();
    echo \"Nombre de projets retournés: \" . \$projects->count() . \"\n\";
    echo \"IDs: \" . \$projects->pluck('id')->implode(', ') . \"\n\";
    foreach (\$projects as \$p) {
        echo \"  - Projet #{\$p->id}: {\$p->name}\n\";
    }
}
"

echo ""
echo "3. Génération d'un token pour tester l'API..."
TOKEN=$(php artisan tinker --execute="
\$user = App\Models\User::where('email', '$EMAIL')->first();
if (\$user) {
    \$user->tokens()->where('name', 'test-mobile')->delete();
    \$token = \$user->createToken('test-mobile')->plainTextToken;
    echo \$token;
}
" | tr -d '\n' | tr -d ' ')

if [ -z "$TOKEN" ]; then
    echo "❌ Impossible de générer un token"
    exit 1
fi

echo "Token généré"
echo ""

echo "4. Test de l'endpoint /api/v1/projects..."
echo ""

# Récupérer l'URL
APP_URL="http://localhost"
if [ -f .env ]; then
    ENV_URL=$(grep "^APP_URL" .env | cut -d '=' -f2 | tr -d ' ' | tr -d '"' | tr -d "'")
    if [ ! -z "$ENV_URL" ] && [ "$ENV_URL" != "" ]; then
        APP_URL="$ENV_URL"
    fi
fi

echo "URL: $APP_URL/api/v1/projects"
echo ""

# Faire la requête
RESPONSE=$(curl -s -X GET "$APP_URL/api/v1/projects" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN")

echo "Réponse JSON:"
echo "$RESPONSE" | python3 -m json.tool 2>/dev/null || echo "$RESPONSE"

echo ""
echo "----------------------------------------"

# Compter les projets
PROJECT_COUNT=$(echo "$RESPONSE" | grep -o '"id":[0-9]*' | wc -l)
echo "Nombre de projets dans la réponse: $PROJECT_COUNT"

# Extraire les IDs
echo "IDs des projets:"
echo "$RESPONSE" | grep -o '"id":[0-9]*' | grep -o '[0-9]*' | tr '\n' ', ' | sed 's/,$/\n/'

echo ""
echo ""
echo "5. Test de l'endpoint /api/v1/dashboard..."
echo ""

DASHBOARD_RESPONSE=$(curl -s -X GET "$APP_URL/api/v1/dashboard" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN")

echo "$DASHBOARD_RESPONSE" | python3 -m json.tool 2>/dev/null || echo "$DASHBOARD_RESPONSE"

TOTAL_PROJECTS=$(echo "$DASHBOARD_RESPONSE" | grep -o '"total_projects":[0-9]*' | grep -o '[0-9]*')
echo ""
echo "Total projets dans le dashboard: $TOTAL_PROJECTS"

echo ""
echo "=========================================="
echo "RÉSUMÉ"
echo "=========================================="
echo ""
echo "Si l'API retourne plus de 1 projet → Problème dans le backend"
echo "Si l'API retourne 1 projet mais l'app mobile en montre plus → Problème dans l'app mobile (cache)"
echo ""
