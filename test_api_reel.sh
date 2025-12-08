#!/bin/bash

# Test de l'API avec le token réel de l'utilisateur

EMAIL="aminefaye@gmail.com"

echo "=========================================="
echo "TEST API RÉEL - aminefaye@gmail.com"
echo "=========================================="
echo ""

echo "1. Génération d'un token pour l'utilisateur..."
php artisan tinker --execute="
\$user = App\Models\User::where('email', '$EMAIL')->first();
if (\$user) {
    // Supprimer les anciens tokens de test
    \$user->tokens()->where('name', 'test-api')->delete();
    // Créer un nouveau token
    \$token = \$user->createToken('test-api')->plainTextToken;
    echo \$token;
}
" > /tmp/token.txt

TOKEN=$(cat /tmp/token.txt | tr -d '\n' | tr -d ' ')

if [ -z "$TOKEN" ] || [ "$TOKEN" == "" ]; then
    echo "❌ Impossible de générer un token"
    exit 1
fi

echo "Token généré"
echo ""

echo "2. Test de l'endpoint /api/v1/projects..."
echo ""

# Récupérer l'URL depuis .env
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
echo "Réponse de l'API:"
echo "----------------------------------------"
RESPONSE=$(curl -s -X GET "$APP_URL/api/v1/projects" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN")

echo "$RESPONSE" | python3 -m json.tool 2>/dev/null || echo "$RESPONSE"

echo ""
echo "----------------------------------------"
echo ""

# Extraire le nombre de projets
PROJECT_COUNT=$(echo "$RESPONSE" | grep -o '"id":[0-9]*' | wc -l)
echo "Nombre de projets retournés: $PROJECT_COUNT"

# Extraire les IDs
echo "IDs des projets retournés:"
echo "$RESPONSE" | grep -o '"id":[0-9]*' | grep -o '[0-9]*' | tr '\n' ', ' | sed 's/,$/\n/'

echo ""
echo ""

echo "3. Test de l'endpoint /api/v1/dashboard..."
echo ""

DASHBOARD_RESPONSE=$(curl -s -X GET "$APP_URL/api/v1/dashboard" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN")

echo "$DASHBOARD_RESPONSE" | python3 -m json.tool 2>/dev/null || echo "$DASHBOARD_RESPONSE"

echo ""
echo "----------------------------------------"

# Extraire total_projects
TOTAL_PROJECTS=$(echo "$DASHBOARD_RESPONSE" | grep -o '"total_projects":[0-9]*' | grep -o '[0-9]*')
echo "Total projets dans le dashboard: $TOTAL_PROJECTS"

echo ""
echo "4. Vérification des logs..."
echo ""
tail -n 30 storage/logs/laravel.log | grep -E "API Projects|accessibleByUser" | tail -n 10

echo ""
echo "=========================================="
echo "RÉSUMÉ"
echo "=========================================="
echo ""
echo "Si l'API retourne plus de 2 projets, le problème est dans l'API"
echo "Si l'API retourne 2 projets mais l'app mobile en montre plus,"
echo "le problème est dans l'application mobile (cache ou autre)"
echo ""
